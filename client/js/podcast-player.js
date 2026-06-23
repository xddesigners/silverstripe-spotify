(function () {
    function init() {
        document.querySelectorAll('.js-podcast-play').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var episode = btn.closest('.podcast-episode');
                var embed = episode.querySelector('.podcast-episode__embed');
                var iframe = embed.querySelector('iframe');
                var isOpen = !embed.hidden;
                var playText = btn.dataset.play || 'Play';
                var closeText = btn.dataset.close || 'Close player';

                document.querySelectorAll('.podcast-episode__embed').forEach(function (el) {
                    el.hidden = true;
                });
                document.querySelectorAll('.js-podcast-play').forEach(function (el) {
                    el.textContent = el.dataset.play || playText;
                });

                if (!isOpen) {
                    if (!iframe.src && iframe.dataset.src) {
                        iframe.src = iframe.dataset.src;
                    }
                    embed.hidden = false;
                    btn.textContent = closeText;
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
