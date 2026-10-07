import http from 'node:http';
import {readFile,stat} from 'node:fs/promises';
import {resolve,extname,sep,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
const root=dirname(fileURLToPath(import.meta.url));
const port=Number(process.env.PORT||4173);
const types={'.html':'text/html','.js':'text/javascript','.mjs':'text/javascript','.css':'text/css','.json':'application/json','.png':'image/png','.jpg':'image/jpeg','.svg':'image/svg+xml','.woff':'font/woff','.txt':'text/plain'};
http.createServer(async(req,res)=>{try{let p=decodeURIComponent(new URL(req.url,'http://localhost').pathname);if(p.endsWith('/'))p+='index.html';const file=resolve(root,'.'+p);if(!file.startsWith(root+sep)){res.writeHead(403);res.end();return;}if(!(await stat(file)).isFile())throw Error();res.writeHead(200,{'Content-Type':(types[extname(file)]||'application/octet-stream')+(types[extname(file)]?.startsWith('text/')?'; charset=utf-8':''),'Cache-Control':'no-store'});res.end(await readFile(file));}catch{res.writeHead(404);res.end('No encontrado');}}).listen(port,'127.0.0.1',()=>console.log(`Klini: http://localhost:${port}`));
