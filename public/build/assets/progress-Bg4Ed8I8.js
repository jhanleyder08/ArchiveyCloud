import{c as d,a as $}from"./app-logo-icon-BhHHxLi4.js";import{r as u,j as i}from"./app-BV8yjCq7.js";import{c as I}from"./index-C-gP6wGF.js";import{P as f}from"./label-CN-Cuv_T.js";/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const w=[["path",{d:"M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3",key:"1xhozi"}]],G=d("Headphones",w);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const V=[["rect",{width:"18",height:"18",x:"3",y:"3",rx:"2",ry:"2",key:"1m3agn"}],["circle",{cx:"9",cy:"9",r:"2",key:"af1f0g"}],["path",{d:"m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21",key:"1xmnt7"}]],X=d("Image",V);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const k=[["path",{d:"M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4",key:"ih7n3h"}],["polyline",{points:"17 8 12 3 7 8",key:"t8dd8p"}],["line",{x1:"12",x2:"12",y1:"3",y2:"15",key:"widbto"}]],z=d("Upload",k);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const E=[["path",{d:"m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5",key:"ftymec"}],["rect",{x:"2",y:"6",width:"14",height:"12",rx:"2",key:"158x01"}]],F=d("Video",E);var c="Progress",p=100,[M,q]=I(c),[R,j]=M(c),x=u.forwardRef((e,a)=>{const{__scopeProgress:n,value:o=null,max:r,getValueLabel:N=L,..._}=e;(r||r===0)&&!m(r)&&console.error(A(`${r}`,"Progress"));const t=m(r)?r:p;o!==null&&!v(o,t)&&console.error(H(`${o}`,"Progress"));const s=v(o,t)?o:null,b=l(s)?N(s,t):void 0;return i.jsx(R,{scope:n,value:s,max:t,children:i.jsx(f.div,{"aria-valuemax":t,"aria-valuemin":0,"aria-valuenow":l(s)?s:void 0,"aria-valuetext":b,role:"progressbar","data-state":y(s,t),"data-value":s??void 0,"data-max":t,..._,ref:a})})});x.displayName=c;var g="ProgressIndicator",h=u.forwardRef((e,a)=>{const{__scopeProgress:n,...o}=e,r=j(g,n);return i.jsx(f.div,{"data-state":y(r.value,r.max),"data-value":r.value??void 0,"data-max":r.max,...o,ref:a})});h.displayName=g;function L(e,a){return`${Math.round(e/a*100)}%`}function y(e,a){return e==null?"indeterminate":e===a?"complete":"loading"}function l(e){return typeof e=="number"}function m(e){return l(e)&&!isNaN(e)&&e>0}function v(e,a){return l(e)&&!isNaN(e)&&e<=a&&e>=0}function A(e,a){return`Invalid prop \`max\` of value \`${e}\` supplied to \`${a}\`. Only numbers greater than 0 are valid max values. Defaulting to \`${p}\`.`}function H(e,a){return`Invalid prop \`value\` of value \`${e}\` supplied to \`${a}\`. The \`value\` prop must be:
  - a positive number
  - less than the value passed to \`max\` (or ${p} if no \`max\` prop is set)
  - \`null\` or \`undefined\` if the progress is indeterminate.

Defaulting to \`null\`.`}var P=x,S=h;const C=u.forwardRef(({className:e,value:a,...n},o)=>i.jsx(P,{ref:o,className:$("relative h-4 w-full overflow-hidden rounded-full bg-secondary",e),...n,children:i.jsx(S,{className:"h-full w-full flex-1 bg-primary transition-all",style:{transform:`translateX(-${100-(a||0)}%)`}})}));C.displayName=P.displayName;export{G as H,X as I,C as P,z as U,F as V};
