function getNeighbors(x, y, isWall) {
    const directions = [[1, 0], [-1, 0], [0, 1], [0, -1]];
    const neighbors = [];

    for (const [dx, dy] of directions) {
        const nx = x + dx;
        const ny = y + dy;
        if (!isWall(nx, ny)) {
            neighbors.push({ x: nx, y: ny });
        }
    }

    return neighbors;
}

export function bfsNextStep(startX, startY, targetX, targetY, isWall) {
    const startKey = `${startX},${startY}`;
    const targetKey = `${targetX},${targetY}`;
    const queue = [{ x: startX, y: startY }];
    const visited = new Set([startKey]);
    const parent = {};

    while (queue.length > 0) {
        const current = queue.shift();
        const currentKey = `${current.x},${current.y}`;

        if (currentKey === targetKey) {
            let step = targetKey;
            while (parent[step] && parent[step] !== startKey) {
                step = parent[step];
            }
            if (!parent[step]) {
                return null;
            }
            const [x, y] = step.split(',').map(Number);
            return { x, y };
        }

        for (const next of getNeighbors(current.x, current.y, isWall)) {
            const key = `${next.x},${next.y}`;
            if (!visited.has(key)) {
                visited.add(key);
                parent[key] = currentKey;
                queue.push(next);
            }
        }
    }

    return null;
}
