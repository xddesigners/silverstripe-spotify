<?php

namespace XD\Spotify\Models;

use LeKoala\CmsActions\CustomAction;
use LeKoala\CmsActions\CustomLink;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use XD\Spotify\Services\SpotifyService;

/**
 * Class Podcast
 * @package XD\Spotify\Models
 *
 * @method Image Image()
 * @method HasManyList Episodes()
 */
class Podcast extends DataObject
{
    private static string $table_name = 'Podcast';
    private static string $singular_name = 'Podcast';
    private static string $plural_name = 'Podcasts';

    private static array $db = [
        'Title'         => 'Varchar(500)',
        'SpotifyShowID' => 'Varchar(100)',
        'Description'   => 'Text',
        'ImageURL'      => 'Varchar(500)',
        'LastSynced'    => 'DBDatetime',
    ];

    private static array $has_one = [
        'Image' => Image::class,
    ];

    private static array $owns = [
        'Image',
    ];

    private static array $has_many = [
        'Episodes' => SpotifyEpisode::class,
    ];

    private static array $summary_fields = [
        'Image.StripThumbnail' => 'Image',
        'Title'                => 'Title',
        'SpotifyShowID'        => 'Spotify Show ID',
        'LastSynced.Nice'      => 'Last synchronised',
    ];

    public function fieldLabels($includeRelations = true)
    {
        $labels = parent::fieldLabels($includeRelations);
        $labels['Image'] = _t(__CLASS__ . '.IMAGE', 'Image');
        return $labels;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('SpotifyShowID', _t(__CLASS__ . '.SPOTIFYSHOWID', 'Spotify Show ID'))
                ->setDescription(_t(__CLASS__ . '.SPOTIFYSHOWID_DESCRIPTION', 'Found in the Spotify URL: open.spotify.com/show/<strong>ID</strong>')),
            TextField::create('Title', _t(__CLASS__ . '.TITLE', 'Title'))
                ->setDescription(_t(__CLASS__ . '.TITLE_DESCRIPTION', 'If left blank, the title will be fetched from Spotify')),
            ReadonlyField::create('Description', _t(__CLASS__ . '.DESCRIPTION', 'Description')),
            ReadonlyField::create('ImageURL', _t(__CLASS__ . '.IMAGEURL', 'Image URL')),
            ReadonlyField::create('LastSynced', _t(__CLASS__ . '.LASTSYNCED', 'Last synchronised')),
        ]);

        if ($this->isInDB()) {
            $config = GridFieldConfig_RecordEditor::create();
            $config->removeComponentsByType(GridFieldAddNewButton::class);
            $config->removeComponentsByType(GridFieldDeleteAction::class);

            $tab = $fields->findOrMakeTab('Root.Episodes');
            $tab->setTitle(_t(__CLASS__ . '.EPISODES_TAB', 'Episodes'));

            $fields->addFieldToTab('Root.Episodes',
                GridField::create('Episodes', _t(__CLASS__ . '.EPISODES', 'Episodes'), $this->Episodes(), $config)
            );
        }

        return $fields;
    }

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        if ($this->isInDB() && $this->SpotifyShowID) {
            $sync = CustomAction::create('syncEpisodes', _t(__CLASS__ . '.SYNC_BUTTON', 'Sync episodes from Spotify'));
            $sync->setButtonType('outline-primary');
            $actions->push($sync);

            $link = CustomLink::create(
                'open_spotify',
                _t(__CLASS__ . '.OPEN_SPOTIFY', 'Open on Spotify'),
                "https://open.spotify.com/show/{$this->SpotifyShowID}"
            );
            $link->setButtonType('outline-dark');
            $actions->push($link);
        }

        return $actions;
    }

    public function getShowEmbedURL(): string
    {
        return "https://open.spotify.com/embed/show/{$this->SpotifyShowID}?utm_source=generator";
    }

    public function forTemplate()
    {
        return $this->renderWith(static::class);
    }

    public function getLatestEpisode(): ?SpotifyEpisode
    {
        return $this->Episodes()->filter('Published', true)->first();
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->SpotifyShowID && str_contains($this->SpotifyShowID, 'spotify.com')) {
            if (preg_match('#/show/([a-zA-Z0-9]+)#', $this->SpotifyShowID, $m)) {
                $this->SpotifyShowID = $m[1];
            }
        }

        if ($this->SpotifyShowID && ($this->isChanged('SpotifyShowID') || !$this->Title)) {
            try {
                $service = new SpotifyService();
                $show = $service->getShow($this->SpotifyShowID);

                if (!$this->Title) {
                    $this->Title = $show['name'] ?? '';
                }
                $this->Description = $show['description'] ?? '';

                $newImageURL = $show['images'][0]['url'] ?? '';
                if ($newImageURL && ($newImageURL !== $this->ImageURL || !$this->ImageID)) {
                    $this->ImageURL = $newImageURL;
                    $image = $this->storeRemoteImage($service, $newImageURL, 'podcast', $this->SpotifyShowID);
                    if ($image) {
                        $this->ImageID = $image->ID;
                    }
                }
            } catch (\Exception $e) {
                // Don't block the save if the API is unreachable
            }
        }
    }

    public function syncEpisodes(): string
    {
        $service = new SpotifyService();

        try {
            $show = $service->getShow($this->SpotifyShowID);
            $this->Description = $show['description'] ?? '';

            $newImageURL = $show['images'][0]['url'] ?? '';
            if ($newImageURL && ($newImageURL !== $this->ImageURL || !$this->ImageID)) {
                $this->ImageURL = $newImageURL;
                $image = $this->storeRemoteImage($service, $newImageURL, 'podcast', $this->SpotifyShowID);
                if ($image) {
                    $this->ImageID = $image->ID;
                }
            }

            $this->write();
            $episodes = $service->getShowEpisodes($this->SpotifyShowID);
        } catch (\Exception $e) {
            throw new \Exception(_t(__CLASS__ . '.SYNC_ERROR', 'Spotify API error: {message}', ['message' => $e->getMessage()]));
        }

        $synced = 0;
        $episodeFolder = 'podcast/' . $this->SpotifyShowID;

        foreach ($episodes as $data) {
            if (empty($data['id'])) {
                continue;
            }

            $episode = SpotifyEpisode::get()->filter('SpotifyID', $data['id'])->first()
                ?: SpotifyEpisode::create();

            $isNew = !$episode->isInDB();

            $episode->SpotifyID   = $data['id'];
            $episode->Title       = $data['name'] ?? '';
            $episode->Content     = $data['html_description'] ?? $data['description'] ?? '';
            $episode->ReleaseDate = $data['release_date'] ?? null;
            $episode->DurationMS  = $data['duration_ms'] ?? 0;
            $episode->ExternalURL = $data['external_urls']['spotify'] ?? '';
            $episode->PodcastID   = $this->ID;

            if ($isNew) {
                $episode->Published = true;
            }

            $newEpisodeImageURL = $data['images'][0]['url'] ?? '';
            if ($newEpisodeImageURL && ($newEpisodeImageURL !== $episode->ImageURL || !$episode->ImageID)) {
                $episode->ImageURL = $newEpisodeImageURL;
                $image = $this->storeRemoteImage($service, $newEpisodeImageURL, $episodeFolder, $data['id']);
                if ($image) {
                    $episode->ImageID = $image->ID;
                }
            }

            $episode->write();
            $synced++;
        }

        $this->LastSynced = date('Y-m-d H:i:s');
        $this->write();

        return _t(__CLASS__ . '.SYNC_SUCCESS', 'Synced {count} episodes from Spotify.', ['count' => $synced]);
    }

    public function getPublishedEpisodes()
    {
        return $this->Episodes()->filter('Published', true)->sort('ReleaseDate', 'DESC');
    }

    private function storeRemoteImage(SpotifyService $service, string $url, string $folder, string $filename): ?Image
    {
        $content = $service->downloadImage($url);
        if (!$content) {
            return null;
        }

        $assetFolder = Folder::find_or_make($folder);
        $image = Image::create();
        $image->setFromString($content, $filename . '.jpg');
        $image->ParentID = $assetFolder->ID;
        $image->write();
        $image->publishSingle();

        return $image;
    }
}
