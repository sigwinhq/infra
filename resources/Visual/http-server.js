const ROOT = process.env.ROOT;
const SERVER_PORT = process.env.SERVER_PORT;

const fs = require('fs');
const http = require('http');
const types = [
    {ext: '.stream.html', type: 'text/vnd.turbo-stream.html'},
    {ext: '.js', type: 'text/javascript'},
    {ext: '.js', type: 'text/javascript'},
    {ext: '.css', type: 'text/css'},
    {ext: '.json', type: 'application/json'},
    {ext: '.png', type: 'image/png'},
    {ext: '.jpg', type: 'image/jpeg'},
    {ext: '.jpeg', type: 'image/jpeg'},
    {ext: '.gif', type: 'image/gif'},
    {ext: '.webp', type: 'image/webp'},
    {ext: '.avif', type: 'image/avif'},
    {ext: '.svg', type: 'image/svg+xml'},
    {ext: '.woff', type: 'font/woff'},
    {ext: '.woff2', type: 'font/woff2'},
    {ext: '.ttf', type: 'font/ttf'},
]

const server = http.createServer(function (req, res) {
    const filePath = '.' + req.url;
    let contentType = 'text/html';
    for (const type of types) {
        if (filePath.endsWith(type.ext)) {
            contentType = type.type;
            break;
        }
    }

    fs.readFile(decodeURI(ROOT + req.url.replace(/\?[^?]+/, '')), function (err, data) {
        if (err) {
            console.error(err);
            res.writeHead(404);
            res.end(JSON.stringify(err));
            return;
        }
        res.writeHead(200, { 'Content-Type': contentType });
        if (filePath.endsWith('.html')) {
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
// and inject a script to notify Backstop when all visible images have been loaded
function modifyResponse(res) {
    const baseWithoutProtocol = process.env.BASE_URL.replace('file://localhost', '');
    const checkLoadedImagesScript = fs.readFileSync(__dirname + '/check-loaded-images.js', {encoding: 'utf8', flag: 'r'});
    res = res + `<script>${checkLoadedImagesScript}</script>`;
    return res.replaceAll(baseWithoutProtocol, '');
}

module.exports = server;
