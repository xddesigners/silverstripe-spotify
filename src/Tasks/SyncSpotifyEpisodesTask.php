<?php

namespace XD\Spotify\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use XD\Spotify\Models\Podcast;

class SyncSpotifyEpisodesTask extends BuildTask
{
    protected static string $commandName = 'SyncSpotifyEpisodesTask';

    protected string $title = 'Sync Spotify Episodes';

    protected static string $description = 'Fetches podcast episodes and images from the Spotify API for all Podcasts.';

    private ?PolyOutput $output = null;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->output = $output;

        $podcasts = Podcast::get()->filter('SpotifyShowID:not', ['', null]);

        if (!$podcasts->count()) {
            $this->log('No Podcasts with a SpotifyShowID found.');
            return Command::SUCCESS;
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

        return Command::SUCCESS;
    }

    private function log(string $message): void
    {
        $this->output?->writeln($message);
    }
}
