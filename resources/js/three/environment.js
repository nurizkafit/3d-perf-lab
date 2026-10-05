// Context collection (BENCHMARK.md §3.4) and device class suggestion (§7.1). The suggestion is a hint only:
// the user always confirms a class from the admin-managed list. No Three.js import, so /device stays light.

/** WebGL facts from a throwaway context. */
function webglInfo() {
    const canvas = document.createElement('canvas');
    const gl = canvas.getContext('webgl2') ?? canvas.getContext('webgl');
    if (!gl) return { webgl_version: null, gpu_vendor: null, gpu_renderer: null, max_texture_size: null };

    const debug = gl.getExtension('WEBGL_debug_renderer_info');
    const info = {
        webgl_version: gl instanceof WebGL2RenderingContext ? 2 : 1,
        // Unmasked strings only when the browser exposes them (browser-reported).
        gpu_vendor: debug ? gl.getParameter(debug.UNMASKED_VENDOR_WEBGL) : gl.getParameter(gl.VENDOR),
        gpu_renderer: debug ? gl.getParameter(debug.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER),
        max_texture_size: gl.getParameter(gl.MAX_TEXTURE_SIZE),
    };
    gl.getExtension('WEBGL_lose_context')?.loseContext();

    return info;
}

/** All context signals the browser exposes. Missing values are null, never guessed. */
export async function collectContext(extra = {}) {
    const uaData = navigator.userAgentData;
    let battery = null;
    try {
        const status = await navigator.getBattery?.();
        battery = status ? { charging: status.charging, level: status.level } : null;
    } catch {
        battery = null;
    }

    return {
        user_agent: navigator.userAgent,
        user_agent_data: uaData ? { mobile: uaData.mobile, platform: uaData.platform, brands: uaData.brands?.map((b) => `${b.brand} ${b.version}`) } : null,
        platform: navigator.platform || null,
        hardware_concurrency: navigator.hardwareConcurrency ?? null,
        device_memory: navigator.deviceMemory ?? null,
        max_touch_points: navigator.maxTouchPoints ?? 0,
        screen: { width: screen.width, height: screen.height },
        device_pixel_ratio: window.devicePixelRatio,
        connection_effective_type: navigator.connection?.effectiveType ?? null,
        battery,
        ...webglInfo(),
        timestamp: new Date().toISOString(),
        ...extra,
    };
}

function detectPlatform(context) {
    const ua = context.user_agent;
    const touchMac = /Macintosh/.test(ua) && context.max_touch_points > 1; // iPadOS reports as macOS
    if (/iPhone|iPod/.test(ua)) return 'ios';
    if (/iPad/.test(ua) || touchMac) return 'ios';
    if (/Android/.test(ua)) return 'android';
    if (/CrOS/.test(ua)) return 'chromeos';
    if (/Windows/.test(ua)) return 'windows';
    if (/Macintosh|Mac OS X/.test(ua)) return 'macos';
    if (/Linux/.test(ua)) return 'linux';

    return null;
}

function detectCategory(context, platform) {
    const ua = context.user_agent;
    const shortSide = Math.min(context.screen.width, context.screen.height);
    if (/iPad/.test(ua) || (/Macintosh/.test(ua) && context.max_touch_points > 1)) return 'tablet';
    if (/iPhone|iPod/.test(ua)) return 'phone';
    if (context.user_agent_data?.mobile === true || /Mobi/.test(ua)) return 'phone';
    if (platform === 'android') return shortSide >= 600 ? 'tablet' : 'phone';

    return 'desktop_or_laptop';
}

/** GPU family → Android tier (BENCHMARK.md §7.1). No match → null (no tier preselected). */
function androidTier(gpu) {
    if (!gpu) return null;
    if (/Adreno.*\b7\d\d\b|Immortalis|Mali-G7\d/i.test(gpu)) return 'Flagship';
    if (/Adreno.*\b6\d\d\b|Mali-G6\d/i.test(gpu)) return 'Mid-range';
    if (/Adreno.*\b[1-5]\d\d\b|Mali-[T4]|Mali-G[1-5]\d/i.test(gpu)) return 'Entry';

    return null;
}

/**
 * Suggest a device class from the list (BENCHMARK.md §7.1).
 * @param {object} context collectContext() result
 * @param {{id: number, name: string, category: string, platform: string}[]} classes active classes
 * @returns {{ category: string, platform: string|null, gpuType: string|null, tier: string|null, classId: number|null, summary: string }}
 */
export function suggestDeviceClass(context, classes) {
    const platform = detectPlatform(context);
    const category = detectCategory(context, platform);
    const gpu = context.gpu_renderer ?? '';
    let tier = null;
    let gpuType = null;

    if (category === 'phone' && platform === 'ios') tier = 'Standard'; // GPU is masked as "Apple GPU": the user chooses
    if (category === 'phone' && platform === 'android') tier = androidTier(gpu);
    if (category === 'desktop_or_laptop') {
        gpuType = /NVIDIA|GeForce|RTX|GTX|Radeon RX/i.test(gpu) ? 'Dedicated GPU' : 'Integrated GPU';
    }

    // Preselect only when one class fits; laptop versus desktop cannot be detected, so it is never preselected.
    const candidates = classes.filter((c) => {
        if (category === 'desktop_or_laptop') return false;
        if (c.category !== category) return false;
        if (c.platform !== 'any' && c.platform !== platform) return false;

        return category === 'tablet' || (tier !== null && c.name.includes(tier));
    });

    const categoryLabel = { phone: 'Phone', tablet: 'Tablet', desktop_or_laptop: 'Laptop or desktop' }[category];
    const platformLabel = { ios: 'iOS / iPadOS', android: 'Android', windows: 'Windows', macos: 'macOS', linux: 'Linux', chromeos: 'ChromeOS' }[platform] ?? 'unknown platform';

    return {
        category,
        platform,
        gpuType,
        tier,
        classId: candidates.length === 1 ? candidates[0].id : null,
        summary: [categoryLabel, platformLabel, gpuType ?? tier].filter(Boolean).join(' · '),
    };
}
