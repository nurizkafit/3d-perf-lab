// File name detection: author_tool_object.glb
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseFileName } from '../../resources/js/features/filename-meta.js';

const lists = {
    tools: [{ id: 1, name: 'Blender' }, { id: 2, name: 'Cinema 4D' }],
    objects: [{ id: 7, name: 'Chair' }, { id: 8, name: 'Space Ship' }],
};

test('detects author, tool, and object', () => {
    assert.deepEqual(parseFileName('Jane_Blender_Chair.glb', lists), { author: 'Jane', tool_id: '1', object_id: '7' });
});

test('matching ignores case, spaces, and punctuation', () => {
    assert.deepEqual(parseFileName('jane_cinema4d_space-ship.gltf', lists), { author: 'jane', tool_id: '2', object_id: '8' });
});

test('unknown tool or object stays empty', () => {
    assert.deepEqual(parseFileName('Jane_Maya_Table.glb', lists), { author: 'Jane', tool_id: '', object_id: '' });
});

test('a name with fewer than three parts detects nothing', () => {
    assert.deepEqual(parseFileName('Jane_Blender.glb', lists), { author: '', tool_id: '', object_id: '' });
    assert.deepEqual(parseFileName('chair.glb', lists), { author: '', tool_id: '', object_id: '' });
});

test('extra parts join into the object name', () => {
    assert.equal(parseFileName('Jane_Blender_Space_Ship.glb', lists).object_id, '8');
});
