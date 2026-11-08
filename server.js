const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');

const PORT = 3456;
const REMOTE_HOST = 'katechstore.net';

const server = http.createServer(async (req, res) => {
  // CORS configuration
  res.setHeader('Access-Control-Allow-Origin', req.headers.origin || '*');
  res.setHeader('Access-Control-Allow-Credentials', 'true');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Cookie');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  // Proxy all /api/v1 requests to Hostinger, preserving cookies and credentials
  if (req.url.startsWith('/api/v1')) {
    let bodyData = null;
    if (['POST', 'PATCH', 'PUT'].includes(req.method)) {
      bodyData = await new Promise(r => {
        let b = '';
        req.on('data', c => b += c);
        req.on('end', () => r(b));
      });
    }

    const headers = {
      'Accept': 'application/json',
      ...(req.headers.cookie ? { 'Cookie': req.headers.cookie } : {}),
      ...(req.headers['content-type'] ? { 'Content-Type': req.headers['content-type'] } : {}),
      ...(bodyData ? { 'Content-Length': Buffer.byteLength(bodyData) } : {})
    };

    const proxyReq = https.request({
      hostname: REMOTE_HOST,
      path: req.url,
      method: req.method,
      headers: headers
    }, proxyRes => {
      // Forward Set-Cookie and other headers back to client
      const resHeaders = { ...proxyRes.headers };
      res.writeHead(proxyRes.statusCode, resHeaders);
      proxyRes.pipe(res);
    });

    proxyReq.on('error', err => {
      console.error('[Proxy Error]', err);
      res.writeHead(502, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ error: err.message }));
    });

    if (bodyData) proxyReq.write(bodyData);
    proxyReq.end();
    return;
  }

  // Health check
  if (req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', server: 'KA Tech Caisse Secure', time: new Date() }));
    return;
  }

  // Serve static caisse.html
  const filePath = path.join(__dirname, 'caisse.html');
  fs.readFile(filePath, (err, content) => {
    if (err) {
      res.writeHead(404, { 'Content-Type': 'text/plain' });
      res.end('caisse.html not found');
      return;
    }
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(content);
  });
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`====================================================`);
  console.log(`KA TECH STORE — CAISSE SECURISEE (ADMIN SEULEMENT)`);
  console.log(`Application: http://localhost:${PORT}`);
  console.log(`Synchronisation securisee: https://${REMOTE_HOST}`);
  console.log(`====================================================`);
});
