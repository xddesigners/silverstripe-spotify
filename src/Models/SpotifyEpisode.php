<?php

namespace XD\Spotify\Models;

use SilverStripe\Forms\LiteralField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

/**
 * Class SpotifyEpisode
 * @package XD\Spotify\Models
 *
 * @method Podcast Podcast()
 * @method Image Image()
 */
class SpotifyEpisode extends DataObject
{
    private static string $table_name = 'SpotifyEpisode';
    private static string $singular_name = 'Episode';
    private static string $plural_name = 'Episodes';

    private static array $db = [
        'SpotifyID'  => 'Varchar(100)',
        'Title'      => 'Varchar(500)',
        'Content'    => 'HTMLText',
        'ReleaseDate'=> 'Date',
        'DurationMS' => 'Int',
        'ImageURL'   => 'Varchar(500)',
        'ExternalURL'=> 'Varchar(500)',
        'Published'  => 'Boolean',
    ];

    private static array $has_one = [
        'Podcast' => Podcast::class,
        'Image'   => Image::class,
    ];

    private static array $owns = [
        'Image',
    ];

    private static string $default_sort = 'ReleaseDate DESC';

    private static array $summary_fields = [
        'Image.StripThumbnail' => 'Image',
        'Title'                => 'Title',
        'ReleaseDate'          => 'Date',
        'Published.Nice'       => 'Published',
    ];

    private static array $indexes = [
        'SpotifyID' => ['type' => 'unique', 'columns' => ['SpotifyID']],
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

        $fields->removeByName(['SpotifyID', 'PodcastID', 'ImageURL', 'ExternalURL', 'DurationMS']);

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title',       _t(__CLASS__ . '.TITLE',       'Title')),
            ReadonlyField::create('SpotifyID',   _t(__CLASS__ . '.SPOTIFYID',   'Spotify ID')),
            ReadonlyField::create('ReleaseDate',  _t(__CLASS__ . '.DATE',        'Date')),
            ReadonlyField::create('ExternalURL', _t(__CLASS__ . '.SPOTIFYURL', 'Spotify URL')),
            HTMLEditorField::create('Content',     _t(__CLASS__ . '.CONTENT',    'Content'))->setRows(10),
            CheckboxField::create('Published',   _t(__CLASS__ . '.PUBLISHED',  'Published')),
        ]);

        return $fields;
    }

    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        if ($this->ExternalURL) {
            $link = LiteralField::create(
                'open_spotify',
                sprintf(
                    '<a href="%s" target="_blank" class="btn btn-outline-primary" style="color:#008a00"><span class="btn__title">%s</span></a>',
                    $this->ExternalURL,
                    _t(__CLASS__ . '.OPEN_SPOTIFY', 'Open on Spotify')
                )
            );
            $actions->push($link);
        }

        return $actions;
    }

    public function forTemplate(): string    {
        return $this->renderWith(static::class);
    }

    public function getEmbedURL(): string
    {
        return "https://open.spotify.com/embed/episode/{$this->SpotifyID}?utm_source=generator";
    }

    public function getFormattedDuration(): string
    {
        if (!$this->DurationMS) {
            return '';
        }
        $minutes = floor($this->DurationMS / 60000);
        $seconds = floor(($this->DurationMS % 60000) / 1000);
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
