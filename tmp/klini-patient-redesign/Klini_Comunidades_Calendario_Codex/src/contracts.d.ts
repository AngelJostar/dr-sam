export type Section='communities'|'calendar';
export type AppointmentStatus='confirmed'|'waiting'|'consulting'|'finished'|'cancelled';
export interface Community{id:string;name:string;description:string;category:'wellness'|'sport';joined:boolean;isNew:boolean;members:number;photo:string}
export interface Comment{author:string;text:string}
export interface Post{id:string;author:string;avatar:string;communityId:string;when:string;photos:string[];caption:string;likes:number;liked:boolean;shares:number;saved:boolean;comments:Comment[];hidden?:boolean}
export interface Appointment{id:string;date:string;time:string;duration:number;patient:string;room:string;specialty:string;status:AppointmentStatus}
export interface MountOptions{section?:Section;embedded?:boolean;hideBottomNav?:boolean}
export interface MountedKlini{element:HTMLElement;destroy():void}
export declare function mountKlini(container:HTMLElement,options?:MountOptions):MountedKlini;
