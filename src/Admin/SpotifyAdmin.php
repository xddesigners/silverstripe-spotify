<?php

namespace XD\Spotify\Admin;

use SilverStripe\Admin\ModelAdmin;
use XD\Spotify\Models\Podcast;
use XD\Spotify\Models\SpotifyEpisode;

class SpotifyAdmin extends ModelAdmin
{
    private static string $url_segment = 'spotify';
    private static string $menu_title = 'Podcasts';
    private static string $menu_icon = '/_resources/silverstripe-spotify/client/icons/spotify.svg';

    private static array $managed_models = [
        Podcast::class,
        SpotifyEpisode::class,
    ];
}
