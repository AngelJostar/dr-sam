export const STATUS={confirmed:'Confirmada',waiting:'En espera',consulting:'En consulta',finished:'Finalizada',cancelled:'Cancelada'};
export const dateKey=d=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
export function parseDate(key){const [y,m,d]=key.split('-').map(Number);return new Date(y,m-1,d,12);}
export function shiftDate(key,days){const d=parseDate(key);d.setDate(d.getDate()+days);return dateKey(d);}
export function shiftMonth(key,months){const d=parseDate(key),day=d.getDate();d.setDate(1);d.setMonth(d.getMonth()+months);d.setDate(Math.min(day,new Date(d.getFullYear(),d.getMonth()+1,0).getDate()));return dateKey(d);}
export function monday(key){const d=parseDate(key);return shiftDate(key,-((d.getDay()+6)%7));}
export function monthDays(key){const d=parseDate(key);d.setDate(1);const start=monday(dateKey(d));return Array.from({length:42},(_,i)=>shiftDate(start,i));}
export const minutes=t=>{const [h,m]=t.split(':').map(Number);return h*60+m;};
export const timeLabel=m=>`${String(Math.floor(m/60)).padStart(2,'0')}:${String(m%60).padStart(2,'0')}`;
export function filteredAppointments(items,{date,room='',specialty=''}={}){return items.filter(a=>(!date||a.date===date)&&(!room||a.room===room)&&(!specialty||a.specialty===specialty)).sort((a,b)=>(a.date+a.time).localeCompare(b.date+b.time));}
export function appointmentError(items,a){if(!a.patient?.trim())return 'Escribe el nombre del paciente.';if(!/^\d{4}-\d{2}-\d{2}$/.test(a.date)||dateKey(parseDate(a.date))!==a.date)return 'Revisa la fecha.';if(!/^\d{2}:\d{2}$/.test(a.time)||!Number.isFinite(minutes(a.time))||Number(a.time.split(':')[1])>59||!Number.isInteger(a.duration)||a.duration<=0)return 'Revisa la hora y duración.';const start=minutes(a.time),end=start+a.duration;if(start<480||end>1080||start%30)return 'Selecciona un horario entre 08:00 y 18:00 en intervalos de 30 minutos.';if(!a.room||!a.specialty)return 'Selecciona consultorio y especialidad.';if(a.status!=='cancelled'&&items.some(b=>b.id!==a.id&&b.status!=='cancelled'&&b.room===a.room&&b.date===a.date&&start<minutes(b.time)+b.duration&&end>minutes(b.time)))return 'Ese consultorio ya tiene una cita en ese horario.';return '';}
export function toggleLike(post){return {...post,liked:!post.liked,likes:Math.max(0,post.likes+(post.liked?-1:1))};}
export function visiblePosts(state,category){const ids=new Set(state.communities.filter(c=>category==='mine'?c.joined:category==='new'?c.isNew:c.category===category).map(c=>c.id));return state.posts.filter(p=>!p.hidden&&ids.has(p.communityId));}
export function dailySummary(items){return Object.fromEntries(['total',...Object.keys(STATUS)].map(k=>[k,k==='total'?items.length:items.filter(a=>a.status===k).length]));}
export function csvCell(v){let s=String(v??'');if(/^[=+@\-\t\r]/.test(s))s="'"+s;return '"'+s.replaceAll('"','""')+'"';}
export function reportCsv(items){return '\ufeff'+[['Fecha','Hora','Paciente','Consultorio','Especialidad','Estado'],...items.map(a=>[a.date,a.time,a.patient,a.room,a.specialty,STATUS[a.status]])].map(row=>row.map(csvCell).join(',')).join('\r\n');}
