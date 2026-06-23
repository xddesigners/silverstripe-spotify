# silverstripe-spotify

Spotify podcast integration for SilverStripe. Syncs shows and episodes from the Spotify API and renders embeddable players in your templates.

## Features

- Sync podcast shows and episodes from the Spotify API via a CMS action
- Stores episode metadata: title, description, release date, duration, cover image
- Renders Spotify embed players directly in templates
- `PodcastPage` with paginated episode list
- `PodcastBlock` (Elemental) with episode list or full show embed mode
- Translations: EN, NL, DE, FR, IT, ES

## Requirements

- PHP 8.0+
- SilverStripe CMS 4 or 5
- A [Spotify Developer](https://developer.spotify.com/dashboard) app (Client ID + Secret)

## Installation

```bash
composer require xddesigners/silverstripe-spotify
```

Run a `dev/build` after installation.

## Configuration

Add your Spotify API credentials to your project's `.env`:

```dotenv
SPOTIFY_CLIENT_ID="your_client_id"
SPOTIFY_CLIENT_SECRET="your_client_secret"
```

Optionally override the default market in `app/_config/config.yml`:

```yaml
XD\Spotify\Services\SpotifyService:
  market: 'NL'
```

## Usage

### ModelAdmin

Podcasts are managed under **Podcasts** in the CMS sidebar. Add a podcast, paste the Spotify Show URL or ID into the *Spotify Show ID* field, and save — the title and cover image are fetched automatically. Use the **Sync episodes** button to pull all episodes from Spotify.

### PodcastPage

Add a `PodcastPage` in the site tree, select a podcast, and set the number of episodes per page. Episodes are listed with a paginated embed player.

### PodcastBlock (Elemental)

Add a **Podcast block** to any Elemental area. Choose a podcast and select the display mode:

| Mode | Output |
|---|---|
| Episode list | Paginated list of episode embeds |
| Podcast embed | Single show embed player |

## Templates

The module ships templates for both models. Override them in your project by placing files at the same path under `app/templates/`:

- `XD/Spotify/Models/Podcast.ss`
- `XD/Spotify/Models/SpotifyEpisode.ss`

## License

BSD-3-Clause © [XD Designers](https://xd.nl)
