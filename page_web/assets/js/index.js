document.addEventListener('DOMContentLoaded', function () {

    async function loadStats() {
        try {
            const stats = await getStats();
            document.getElementById('arbres-count').textContent = stats.total;
            document.getElementById('remarquables-count').textContent = stats.remarquables;
            document.getElementById('especes-count').textContent = stats.especes;
        } catch (e) {
            console.error('Failed to load stats:', e);
        }
    }

    loadStats();

});