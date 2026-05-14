export async function getJSON(url, options = {}) {
    const response = await fetch(url, options);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || data.error || `Request failed: ${response.status}`);
    }

    return data;
}

export async function postForm(url, values = {}) {
    const formData = new FormData();

    Object.entries(values).forEach(([key, value]) => {
        formData.append(key, value);
    });

    return getJSON(url, {
        method: 'POST',
        body: formData,
    });
}

const APP_ROOT_URL = (window.APP_ROOT_URL || '').replace(/\/$/, '');

export const api = {
    loadLevels: () => getJSON(`${APP_ROOT_URL}/game/get_levels.php`),
    loadLevel: (levelId) => getJSON(`${APP_ROOT_URL}/game/get_levels.php?id=${encodeURIComponent(levelId)}`),
    saveScore: (payload) => postForm(`${APP_ROOT_URL}/game/save_score.php`, payload),
};