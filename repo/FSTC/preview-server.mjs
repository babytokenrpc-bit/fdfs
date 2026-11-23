import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const types = {
  '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css',
  '.png': 'image/png', '.jpg': 'image/jpeg', '.json': 'application/json', '.woff2': 'font/woff2'
};

http.createServer((req, res) => {
  const url = new URL(req.url, 'http://localhost');
  let requestedPath = decodeURIComponent(url.pathname);
  if (requestedPath === '/' || requestedPath === '') requestedPath = '/index.php';

  if (requestedPath.endsWith('.php')) {
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    fs.createReadStream(path.join(root, 'index.php')).pipe(res);
    return;
  }

  const filePath = path.join(root, requestedPath.replace(/^\/+/, ''));
  if (!filePath.startsWith(root) || !fs.existsSync(filePath) || fs.statSync(filePath).isDirectory()) {
    res.writeHead(404, { 'Content-Type': 'text/plain' });
    res.end('Not found');
    return;
  }
  res.writeHead(200, { 'Content-Type': types[path.extname(filePath)] || 'application/octet-stream' });
  fs.createReadStream(filePath).pipe(res);
}).listen(8080, '0.0.0.0', () => console.log('FSTC preview: http://localhost:8080'));