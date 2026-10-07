import {useEffect,useRef} from 'react';
type Props={section?:'communities'|'calendar';onNavigate?:(target:string)=>void};
/** Copia src y assets a public/klini. Ajusta la ruta si tu app tiene basePath. */
export function KliniPanel({section='communities',onNavigate}:Props){
 const ref=useRef<HTMLDivElement>(null);
 useEffect(()=>{let disposed=false;let element:HTMLElement|undefined;let destroy:(()=>void)|undefined;
  const listener=(event:Event)=>onNavigate?.((event as CustomEvent<{target:string}>).detail.target);
  const url='/klini/src/app.js';
  import(/* @vite-ignore */ url).then(module=>{if(disposed||!ref.current)return;const app=module.mountKlini(ref.current,{section,embedded:true,hideBottomNav:true});element=app.element;destroy=app.destroy;element?.addEventListener('klini:navigate',listener);});
  return()=>{disposed=true;element?.removeEventListener('klini:navigate',listener);destroy?.();};
 },[section,onNavigate]);
 return <div ref={ref}/>;
}
