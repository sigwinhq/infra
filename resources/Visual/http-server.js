const ROOT = process.env.ROOT;
const SERVER_PORT = process.env.SERVER_PORT;

const fs = require('fs');
const path = require('path');
const http = require('http');

const server = http.createServer(function (req, res) {
    var filePath = '.' + req.url;
    var extname = path.extname(filePath);
    var contentType = 'text/html';
    switch (extname) {
        case '.js':
            contentType = 'text/javascript';
            break;
        case '.css':
            contentType = 'text/css';
            break;
        case '.json':
            contentType = 'application/json';
            break;
        case '.png':
            contentType = 'image/png';
            break;
        case '.jpg':
        case '.jpeg':
            contentType = 'image/jpg';
            break;
        case '.gif':
            contentType = 'image/gif';
            break;
        case '.webp':
            contentType = 'image/webp';
            break;
        case '.avif':
            contentType = 'image/avif';
            break;
        case '.svg':
            contentType = 'image/svg+xml';
            break;
        case '.woff':
            contentType = 'font/woff';
            break;
        case '.woff2':
            contentType = 'font/woff2';
            break;
        case '.ttf':
            contentType = 'font/ttf';
            break;
    }

    fs.readFile(decodeURI(ROOT + req.url.replace(/\?[^?]+/, '')), function (err, data) {
        if (err) {
            console.error(err);
            res.writeHead(404);
            res.end(JSON.stringify(err));
            return;
        }
        res.writeHead(200, { 'Content-Type': contentType });
        if (extname === '.html') {
            res.end(modifyResponse(data.toString()), 'utf-8');
        } else {
            res.end(data, 'utf-8');
        }
    });
}).listen(SERVER_PORT);
console.log(`HTTP server listening on ${SERVER_PORT}`);

process.on('SIGTERM', () => {
    console.log('SIGTERM - Closing HTTP server');
    server.close(() => {
        console.log('HTTP server closed');
    });
});

// remove the filesystem part of all paths present in the response
function modifyResponse(res) {
    const baseWithoutProtocol = process.env.BASE_URL.replace('file://localhost', '');
    return res.replaceAll(baseWithoutProtocol, '');
}

module.exports = server;
