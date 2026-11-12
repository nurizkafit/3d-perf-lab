// Detect author, tool, and object from a file name shaped like `author_tool_object.glb`.
// Pure function: the uploader only pre-fills fields; the user can still edit them.

const normalize = (text) => text.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '');

const findId = (list, token) => {
    const wanted = normalize(token);
    if (wanted === '') return '';
    const match = list.find((item) => normalize(item.name) === wanted);

    return match ? String(match.id) : '';
};

/**
 * @param {string} fileName
 * @param {{tools: {id: number|string, name: string}[], objects: {id: number|string, name: string}[]}} lists
 * @returns {{author: string, tool_id: string, object_id: string}} Empty string for each part that is missing or unknown.
 */
export function parseFileName(fileName, { tools = [], objects = [] } = {}) {
    const empty = { author: '', tool_id: '', object_id: '' };
    const base = fileName.replace(/\.[^.]+$/, '');
    const parts = base.split('_').map((part) => part.trim());
    if (parts.length < 3) return empty;

    const [author, tool, ...objectParts] = parts;

    return {
        author,
        tool_id: findId(tools, tool),
        object_id: findId(objects, objectParts.join(' ')),
    };
}
