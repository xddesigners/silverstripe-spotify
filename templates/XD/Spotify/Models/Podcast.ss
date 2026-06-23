<div class="podcast" itemscope itemtype="https://schema.org/PodcastSeries">
    <header class="podcast__header">
        <% if $Image %>
        <div class="podcast__cover">
            <img src="$Image.ScaleWidth(300).URL" alt="$Title.ATT" loading="lazy" width="300" height="300" itemprop="image">
        </div>
        <% end_if %>
        <div class="podcast__meta">
            <h2 class="podcast__title" itemprop="name">$Title</h2>
            <% if $Description %>
            <p class="podcast__description" itemprop="description">$Description</p>
            <% end_if %>
            <% if $SpotifyShowID %>
            <a href="https://open.spotify.com/show/$SpotifyShowID.ATT" target="_blank" rel="noopener noreferrer" class="podcast__spotify-link">
                Open on Spotify
            </a>
            <% end_if %>
        </div>
    </header>
    <% if $PublishedEpisodes %>
    <div class="podcast__episodes">
        <% loop $PublishedEpisodes %>
        $Me
        <% end_loop %>
    </div>
    <% end_if %>
</div>
