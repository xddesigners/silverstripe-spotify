<?php

namespace XD\Spotify\Tasks;

use SilverStripe\Dev\BuildTask;
use XD\Spotify\Models\Podcast;

class SyncSpotifyEpisodesTask extends BuildTask
{
    private static string $segment = 'SyncSpotifyEpisodesTask';

    protected $title = 'Sync Spotify Episodes';

    protected $description = 'Fetches podcast episodes and images from the Spotify API for all Podcasts.';

    public function run($request)
    {
        $podcasts = Podcast::get()->filter('SpotifyShowID:not', ['', null]);

        if (!$podcasts->count()) {
            $this->log('No Podcasts with a SpotifyShowID found.');
            return;
        }

        foreach ($podcasts as $podcast) {
            $this->log("Syncing: {$podcast->Title} (show: {$podcast->SpotifyShowID})");

            try {
                $result = $podcast->syncEpisodes();
                $this->log($result);
            } catch (\Exception $e) {
                $this->log("ERROR: " . $e->getMessage());
            }
        }
    }

    private function log(string $message): void
    {
        echo $message . PHP_EOL;
    }
}
