import http from 'node:http';
import crypto from 'node:crypto';

const PORT = Number(process.env.WEBSOCKET_PORT || 6001);
const clients = new Set();

function encodeWsFrame(text) {
    const payload = Buffer.from(text, 'utf8');
    const len = payload.length;

    let header;
    if (len < 126) {
        header = Buffer.alloc(2);
        header[0] = 0x81; // FIN + text frame
        header[1] = len;
    } else if (len < 65536) {
        header = Buffer.alloc(4);
        header[0] = 0x81;
        header[1] = 126;
        header.writeUInt16BE(len, 2);
    } else {
        header = Buffer.alloc(10);
        header[0] = 0x81;
        header[1] = 127;
        header.writeBigUInt64BE(BigInt(len), 2);
    }

    return Buffer.concat([header, payload]);
}

function decodeWsFrame(buffer) {
    if (buffer.length < 2) return null;
    const firstByte = buffer[0];
    const opcode = firstByte & 0x0f;
    if (opcode === 0x08) return { opcode: 0x08, text: '' }; // Close frame
    if (opcode === 0x09) return { opcode: 0x09, text: '' }; // Ping frame

    const secondByte = buffer[1];
    const isMasked = Boolean(secondByte & 0x80);
    let payloadLen = secondByte & 0x7f;
    let offset = 2;

    if (payloadLen === 126) {
        if (buffer.length < 4) return null;
        payloadLen = buffer.readUInt16BE(2);
        offset = 4;
    } else if (payloadLen === 127) {
        if (buffer.length < 10) return null;
        payloadLen = Number(buffer.readBigUInt64BE(2));
        offset = 10;
    }

    let maskKey = null;
    if (isMasked) {
        if (buffer.length < offset + 4) return null;
        maskKey = buffer.subarray(offset, offset + 4);
        offset += 4;
    }

    if (buffer.length < offset + payloadLen) return null;
    const payload = Buffer.from(buffer.subarray(offset, offset + payloadLen));

    if (isMasked && maskKey) {
        for (let i = 0; i < payload.length; i++) {
            payload[i] ^= maskKey[i % 4];
        }
    }

    return {
        opcode,
        text: payload.toString('utf8'),
    };
}

function broadcast(payloadObj) {
    const frame = encodeWsFrame(JSON.stringify(payloadObj));
    for (const socket of clients) {
        if (!socket.destroyed && socket.writable) {
            try {
                socket.write(frame);
            } catch {
                clients.delete(socket);
            }
        } else {
            clients.delete(socket);
        }
    }
}

const server = http.createServer((req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
    }

    if (req.method === 'POST' && req.url === '/broadcast') {
        let body = '';
        req.on('data', (chunk) => {
            body += chunk.toString();
        });
        req.on('end', () => {
            try {
                const parsed = JSON.parse(body || '{}');
                broadcast(parsed);
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: true, clients: clients.size }));
            } catch (e) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: false, error: e.message }));
            }
        });
        return;
    }

    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', service: 'NALA Realtime WebSocket Server', clients: clients.size }));
});

server.on('upgrade', (req, socket) => {
    const key = req.headers['sec-websocket-key'];
    if (!key) {
        socket.destroy();
        return;
    }

    const acceptKey = crypto
        .createHash('sha1')
        .update(key + '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', 'binary')
        .digest('base64');

    const headers = [
        'HTTP/1.1 101 Switching Protocols',
        'Upgrade: websocket',
        'Connection: Upgrade',
        `Sec-WebSocket-Accept: ${acceptKey}`,
        '\r\n',
    ];

    socket.write(headers.join('\r\n'));
    clients.add(socket);

    // Send initial connected welcome frame
    socket.write(encodeWsFrame(JSON.stringify({ event: 'ws.connected', timestamp: new Date().toISOString() })));

    socket.on('data', (buffer) => {
        try {
            const decoded = decodeWsFrame(buffer);
            if (!decoded) return;
            if (decoded.opcode === 0x08) {
                clients.delete(socket);
                socket.end();
                return;
            }
            if (decoded.opcode === 0x09) {
                // Reply pong
                socket.write(Buffer.from([0x8a, 0x00]));
                return;
            }
            if (decoded.opcode === 0x01 && decoded.text) {
                const msg = JSON.parse(decoded.text);
                if (msg && (msg.event === 'chat.message' || msg.event === 'chat.typing')) {
                    broadcast(msg);
                }
            }
        } catch {
            // Ignore malformed frame
        }
    });

    socket.on('close', () => clients.delete(socket));
    socket.on('error', () => clients.delete(socket));
    socket.on('end', () => clients.delete(socket));
});

server.listen(PORT, '0.0.0.0', () => {
    console.log(`NALA WebSocket Server listening on ws://0.0.0.0:${PORT}`);
});
