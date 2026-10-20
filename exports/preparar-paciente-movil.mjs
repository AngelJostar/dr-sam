import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {execFileSync} from 'node:child_process';

// Export only source and public design assets. Never read .env or runtime data.
const root = path.resolve(import.meta.dirname, '..');
const out = path.join(root, 'exports', 'paciente-movil-2026-10-07');
const download = process.argv.includes('--descargar-imagenes');
const copied = new Map();
const sha = data => crypto.createHash('sha256').update(data).digest('hex');
const slash = value => value.replaceAll('\\', '/');
function files(dir) {
  return fs.readdirSync(path.join(root, dir), {withFileTypes:true}).flatMap(entry => {
    if (entry.isSymbolicLink()) return [];
    const rel = slash(path.join(dir, entry.name));
    return entry.isDirectory() ? files(rel) : [rel];
  });
}
function write(dest, content) {
  const target = path.resolve(out, dest);
  if (!target.startsWith(out + path.sep)) throw new Error('Invalid export destination');
  fs.mkdirSync(path.dirname(target), {recursive:true});
  fs.writeFileSync(target, content);
}
function copy(source, area='codigo') {
  if (/(^|\/)(\.env[^/]*|storage|\.git|node_modules)(\/|$)|\.sql$/.test(source)) throw new Error('Excluded path: '+source);
  const data = fs.readFileSync(path.join(root, source));
  const dest = area + '/' + source;
  write(dest, data);
  copied.set(dest, {source, originalSha256:sha(data)});
}

const sourceRoots = [
  'app/Http/Controllers/Patient/PatientPortalController.php',
  'app/Http/Middleware/EnsureUserRole.php',
  'app/Http/Middleware/PreventStaleSessionPages.php',
  'app/Http/Middleware/EnsureMobileRole.php',
  'app/Http/Controllers/Api/Mobile/V1/PatientReadController.php',
  'app/Http/Controllers/Api/Mobile/V1/AuthController.php',
  'app/Http/Controllers/Api/Mobile/V1/PasswordController.php',
  'app/Http/Controllers/Api/Mobile/V1/MobileDeviceController.php',
];
const appFiles = files('app').filter(file=>file.endsWith('.php'));
const classFiles = new Map(appFiles.map(file=>['App\\'+file.slice(4,-4).replaceAll('/','\\'),file]));
const closure = new Set();
const missingClasses = new Set();
const queue = [...sourceRoots];
while(queue.length) {
  const file = queue.shift();
  if(closure.has(file)) continue;
  closure.add(file); copy(file);
  const source=fs.readFileSync(path.join(root,file),'utf8');
  const namespace=source.match(/namespace\s+([^;]+);/)?.[1];
  const refs=new Set([...source.matchAll(/\bApp\\[A-Za-z_][A-Za-z0-9_\\]*/g)].map(x=>x[0]));
  refs.delete(namespace);
  for(const [name, dependency] of classFiles) {
    if(name.slice(0,name.lastIndexOf('\\'))===namespace) {
      const short=name.slice(name.lastIndexOf('\\')+1);
      if(new RegExp('\\b'+short+'\\b').test(source) && dependency!==file) refs.add(name);
    }
  }
  for(const name of refs) {
    if(classFiles.has(name)) queue.push(classFiles.get(name));
    else missingClasses.add(name);
  }
}

for(const file of files('resources/views/patient')) copy(file);
copy('resources/views/layouts/app.blade.php');
copy('resources/views/components/klini-icon.blade.php');
const styles = [
  'patient-portal-source.css','patient-profile-panel.css','communities.css','policy-saved-overlay.css',
  'patient-history-mobile.css','patient-bottom-nav-mobile.css','patient-calendar-mobile.css','patient-social.css',
];
const scripts = ['klini-profiles.js','patient-profile-panel.js','patient-social.js','communities.js','patient-portal-table-filters.js'];
for(const file of styles) copy('public/css/'+file);
for(const file of scripts) copy('public/js/'+file);
// The shared layout contains a non-patient branch; retain its assets as context.
for(const file of ['drsam.css','klini-brand.css','klini-workspace.css','klini-system.css','klini-carousels.css']) copy('public/css/'+file);
for(const file of ['drsam-table-filters.js','klini-carousels.js']) copy('public/js/'+file);
copy('public/mobile-preview.html');
for(const dir of ['public/images','public/brand','public/vendor/klini-patient/assets','public/vendor/klini-patient/licenses']) {
  for(const file of files(dir)) copy(file);
}
for(const file of ['composer.json','composer.lock','phpunit.xml','tests/TestCase.php','tests/Feature/PatientPortalTest.php',
  'tests/Feature/Api/Mobile/PatientReadApiTest.php','tests/Feature/Api/Mobile/MobileAuthenticationTest.php',
  'config/mobile-api.php']) copy(file);
for(const file of files('tests/Frontend').filter(x=>/CommunityCalendar|PatientSocial|PatientProfiles/.test(x))) copy(file);
for(const file of files('database/migrations')) copy(file,'referencia');
for(const file of ['bootstrap/app.php','bootstrap/providers.php','app/Providers/AppServiceProvider.php',
  'config/auth.php','config/session.php','config/app.php','config/drsam.php',
  'docs/openapi/klini-mobile-v1.yaml']) copy(file,'referencia');

// Route fragments keep the same names expected by Blade without other portals.
const web = fs.readFileSync(path.join(root,'routes/web.php'),'utf8');
const patientStart=web.indexOf("    Route::get('/patient',");
const patientEnd=web.indexOf("    Route::get('/orders',",patientStart);
if(patientStart<0 || patientEnd<patientStart) throw new Error('Patient routes not found');
write('integracion/routes/patient.php', "<?php\n\nuse App\\Http\\Controllers\\Patient\\PatientPortalController;\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::middleware('auth')->group(function (): void {\n"+web.slice(patientStart,patientEnd)+"});\n");
const api=fs.readFileSync(path.join(root,'routes/api.php'),'utf8');
const apiStart=api.indexOf("Route::prefix('mobile/v1')");
const doctorStart=api.indexOf("            Route::prefix('doctor')",apiStart);
const apiTail=api.indexOf('\n        });',doctorStart)+1;
const apiEnd=api.indexOf("Route::middleware(['web', 'auth'])",apiTail);
const apiUses=api.split(/\r?\n/).filter(line=>line.startsWith('use ') && /MobileAuthController|PasswordController|MobileDeviceController|PatientReadController|Facades\\Route/.test(line));
write('integracion/routes/mobile-patient.php','<?php\n\n'+apiUses.join('\n')+'\n\n'+api.slice(apiStart,doctorStart)+api.slice(apiTail,apiEnd));

// Optional cache of public demo images. Only exported copies are rewritten.
const remoteUses=new Map();
for(const [dest] of copied) {
  if(!dest.startsWith('codigo/') || !/\.(js|css|php|html)$/.test(dest)) continue;
  const text=fs.readFileSync(path.join(out,dest),'utf8');
  for(const match of text.matchAll(/https:\/\/images\.unsplash\.com\/[^\s'"<>`\\)]+/g)) {
    const list=remoteUses.get(match[0])||new Set(); list.add(dest);remoteUses.set(match[0],list);
  }
}
const remote=[];
const pending=[...remoteUses];
async function worker() {
  while(pending.length) {
    const [url,usedBy]=pending.shift();
    const filename=sha(url).slice(0,20)+'.jpg';
    const dest='codigo/public/images/patient-export/'+filename;
    let cached=fs.existsSync(path.join(out,dest));
    let error;
    if(!cached && download) {
      try {
        const response=await fetch(url,{signal:AbortSignal.timeout(20000)});
        if(!response.ok) throw new Error('HTTP '+response.status);
        const type=response.headers.get('content-type')||'';
        if(!type.startsWith('image/')) throw new Error('Not an image');
        const bytes=Buffer.from(await response.arrayBuffer());
        if(bytes.length>12*1024*1024) throw new Error('Image too large');
        write(dest,bytes);cached=true;
      } catch(e) { error=e.message; }
    }
    remote.push({url,usedBy:[...usedBy],included:cached,localPath:cached?dest:null,error:error||null});
  }
}
await Promise.all(Array.from({length:5},worker));
for(const image of remote.filter(x=>x.included)) {
  for(const file of image.usedBy) {
    const source=fs.readFileSync(path.join(out,file),'utf8');
    const original='referencia/originales-con-enlaces-remotos/'+file.slice('codigo/'.length);
    if(!fs.existsSync(path.join(out,original))) write(original,fs.readFileSync(path.join(root,file.slice('codigo/'.length))));
    write(file,source.replaceAll(image.url,'/'+image.localPath.slice('codigo/public/'.length)));
  }
}
write('RECURSOS-EXTERNOS.json',JSON.stringify(remote.sort((a,b)=>a.url.localeCompare(b.url)),null,2));

const controller=fs.readFileSync(path.join(root,sourceRoots[0]),'utf8');
const blade=fs.readFileSync(path.join(root,'resources/views/patient/dashboard.blade.php'),'utf8');
const routes=[...blade.matchAll(/route\('([^']+)'/g)].map(x=>x[1]);
write('MAPA-DEL-MODULO.json',JSON.stringify({
  entry:'codigo/resources/views/patient/dashboard.blade.php',
  mobilePreview:'codigo/public/mobile-preview.html',
  mobilePreviewRequires:'Servidor Laravel, ruta /patient y sesión autenticada',
  stylesInOrder:styles.map(x=>'codigo/public/css/'+x), scripts:scripts.map(x=>'codigo/public/js/'+x),
  inlineScript:'La lógica principal de navegación y formularios está dentro de dashboard.blade.php; conservar @json y @csrf.',
  sections:[...blade.matchAll(/<section[^>]+data-patient-view="([^"]+)"/g)].map(x=>x[1]),
  namedRoutes:[...new Set(routes)],
  requiredHostRoutes:['home','login','logout'],
  viewVariables:[...controller.slice(controller.indexOf("return view('patient.dashboard'")).split('    public function storeRegisterAttachment')[0].matchAll(/^\s*'([^']+)'\s*=>/gm)].map(x=>x[1]),
  backendFiles:[...closure].sort(),missingClasses:[...missingClasses],
  localStorage:'Actividad social, comunidades, registros manuales, favoritos, perfiles locales y conversación local. No se exportaron valores de almacenamiento del navegador.',
},null,2));
const version={
  name:'Módulo de paciente · Web adaptable a móvil', version:'2026.10.07-export',
  exportedAt:new Date().toISOString(),timezone:'America/Mexico_City',
  branch:execFileSync('git',['branch','--show-current'],{cwd:root,encoding:'utf8'}).trim(),
  baseCommit:execFileSync('git',['rev-parse','HEAD'],{cwd:root,encoding:'utf8'}).trim(),
  includesWorkingTreeChanges:true,framework:'Laravel 12 / PHP 8.2+',
  assets:{remoteFound:remote.length,remoteIncluded:remote.filter(x=>x.included).length},
  excludes:['.env','Git','vendor PHP','node_modules','Base de datos y SQL','Uploads clínicos','Sesiones','Logs','Datos del navegador'],
};
write('VERSION.json',JSON.stringify(version,null,2));
const guide=fs.readFileSync(path.join(root,'exports','guia-paciente-movil.html'),'utf8');
write('LEEME.html',guide);
for (const file of files('exports/adaptadores')) write(file.slice('exports/'.length), fs.readFileSync(path.join(root,file)));
const all=files(slash(path.relative(root,out))).map(x=>x.slice(slash(path.relative(root,out)).length+1)).filter(x=>!['INVENTARIO.json','SHA256SUMS.txt'].includes(x));
const inventory=all.sort().map(file=>{
  const data=fs.readFileSync(path.join(out,file));
  return {path:file,bytes:data.length,sha256:sha(data),...(copied.get(file)||{generated:true})};
});
write('INVENTARIO.json',JSON.stringify(inventory,null,2));
write('SHA256SUMS.txt',inventory.map(x=>x.sha256+'  '+x.path).join('\n')+'\n');
console.log(JSON.stringify({directory:out,files:inventory.length,bytes:inventory.reduce((n,x)=>n+x.bytes,0),backendClasses:closure.size,missingClasses:[...missingClasses],remoteImages:version.assets},null,2));
