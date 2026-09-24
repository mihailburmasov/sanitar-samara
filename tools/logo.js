// Логотип из Лого.jpg клиента: убирает небо, облака и деревья по цвету, пишет images/logo.webp, favicon.png, apple-touch-icon.png.
// Запуск (нужен sharp): node tools/logo.js
const sharp=require('sharp');
const OUT='D:/Claude/Projects/Sanitar/public';
const SRC='D:/Claude/Создание сайтов/Клиенты/Юра Самара Стройград/Дез. центр/Лого.jpg';
(async()=>{
const {data,info}=await sharp(SRC).raw().toBuffer({resolveWithObject:true});
const W=info.width,H=info.height,C=info.channels,N=W*H;
const clamp=v=>Math.max(0,Math.min(1,v));
const R=p=>data[p*C],G=p=>data[p*C+1],B=p=>data[p*C+2];
const lum=p=>0.3*R(p)+0.59*G(p)+0.11*B(p);
const X=p=>p%W,Y=p=>(p/W)|0;
// мягкие маски
const blueA=new Float32Array(N),greenA=new Float32Array(N);
for(let p=0;p<N;p++){const r=R(p),g=G(p),b=B(p);
 blueA[p]=clamp(((b-r)-35)/45)*clamp((235-lum(p))/40);
 const x=X(p),y=Y(p);
 const inText=y>=86&&y<=152&&x>=95&&x<=575, inLeaf=x>=585&&x<=720&&y>=35&&y<=168;
 greenA[p]=inLeaf?clamp(((g-b)-20)/30)*clamp(((g-r)-0)/25):inText?clamp(((g-b)-20)/30)*clamp(((g-r)-0)/25)*clamp((150-lum(p))/30):0;

}
function comps(A,thr,seed,minSeed){
 const lab=new Int32Array(N).fill(0),keep=new Uint8Array(N);let id=0;
 for(let s=0;s<N;s++){if(A[s]<thr||lab[s])continue;id++;const st=[s],c=[];lab[s]=id;let seeds=0;
  while(st.length){const p=st.pop();c.push(p);if(seed(p))seeds++;const x=X(p),y=Y(p);
   for(const [dx,dy] of [[1,0],[-1,0],[0,1],[0,-1],[1,1],[-1,-1],[1,-1],[-1,1]]){const nx=x+dx,ny=y+dy;if(nx<0||ny<0||nx>=W||ny>=H)continue;const q=ny*W+nx;if(!lab[q]&&A[q]>=thr){lab[q]=id;st.push(q);}}}
  if(seeds>=minSeed)for(const p of c)keep[p]=1;}
 return keep;}
const kb=comps(blueA,0.35,p=>(B(p)-R(p))>100&&lum(p)<100,8);
const kg=comps(greenA,0.35,p=>true,40);
const out=Buffer.alloc(N*4);
const near=(k,p,rad)=>{const x=X(p),y=Y(p);for(let dy=-rad;dy<=rad;dy++)for(let dx=-rad;dx<=rad;dx++){const nx=x+dx,ny=y+dy;if(nx>=0&&ny>=0&&nx<W&&ny<H&&k[ny*W+nx])return 1;}return 0;};
for(let p=0;p<N;p++){
 const ab=kb[p]?blueA[p]:(near(kb,p,1)?blueA[p]:0);
 const ag=kg[p]?greenA[p]:(near(kg,p,1)?greenA[p]:0);
 const al=Math.max(ab,ag);
 out[p*4]=R(p);out[p*4+1]=G(p);out[p*4+2]=B(p);out[p*4+3]=Math.round(al*255);
}
const cut=await sharp(out,{raw:{width:W,height:H,channels:4}}).png().toBuffer();
await sharp(await sharp(cut).trim().png().toBuffer()).resize(480).webp({quality:90,alphaQuality:100}).toFile(OUT+'/images/logo.webp');
// эмблема (кольцо с листьями) для иконок
const em=await sharp(await sharp(cut).extract({left:580,top:0,width:195,height:170}).png().toBuffer()).trim().png().toBuffer();
const icon=async(sz,pad,file)=>{const inner=Math.round(sz*(1-2*pad));const r=await sharp(em).resize(inner,inner,{fit:'contain',background:{r:0,g:0,b:0,alpha:0}}).png().toBuffer();
 await sharp({create:{width:sz,height:sz,channels:4,background:'#ffffff'}}).composite([{input:r,gravity:'center'}]).png().toFile(file);};
await icon(180,.1,OUT+'/apple-touch-icon.png');await icon(48,.04,OUT+'/favicon.png');
})();
