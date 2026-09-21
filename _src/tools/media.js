const sharp = require('sharp');
const ff = require('ffmpeg-static');
const {spawnSync} = require('child_process');
const fs = require('fs'), path = require('path');
const SRC = 'D:/Claude/Создание сайтов/Клиенты/Юра Самара Стройград/Дез. центр';
const OUT = 'D:/Claude/Projects/Sanitar';
for (const d of ['images','images/docs','video']) fs.mkdirSync(path.join(OUT,d),{recursive:true});

const videos = {
  '03':'Видео/19458848524933.mp4','04':'Видео/19458850491013.mp4','05':'Видео/19458868185733.mp4',
  '06':'Видео/19464073710213.mp4','07':'Видео/19605836401285.mp4','08':'Видео/19609328945797.mp4',
  '09':'Видео/19609346378373.mp4','10':'Видео/19789649414789.mp4','11':'Видео/19789655116421.mp4',
  '12':'Видео/19789657213573.mp4','13':'Видео/19792622127749.mp4','15':'Дезинсекция/IMG_0943.MP4',
  '16':'Дезинсекция/IMG_0944.MP4','20':'Дезинфекция/document_5222464650347569207.mp4',
  '21':'Дезинфекция/document_5222464650347569208.mp4','64':'Фунгицидная обработка/IMG_2002.MP4',
  '65':'Фунгицидная обработка/IMG_2004.MP4'
};
(async()=>{
  for (const [id,rel] of Object.entries(videos)) {
    const inp = path.join(SRC,rel), out = path.join(OUT,'video',`work-${id}.mp4`);
    const tmpPoster = path.join(OUT,'video',`_p${id}.jpg`);
    const scale = "scale='if(gt(iw,ih),min(960,iw),-2)':'if(gt(iw,ih),-2,min(960,ih))'";
    let r = spawnSync(ff,['-hide_banner','-loglevel','error','-y','-i',inp,'-t','30','-an','-vf',scale,
      '-c:v','libx264','-preset','slow','-crf','29','-pix_fmt','yuv420p','-movflags','+faststart',out]);
    if (r.status) console.log('ERR video',id,r.stderr.toString());
    spawnSync(ff,['-hide_banner','-loglevel','error','-y','-ss','2','-i',inp,'-frames:v','1','-vf',scale,tmpPoster]);
    await sharp(tmpPoster).webp({quality:74}).toFile(path.join(OUT,'video',`work-${id}.webp`));
    fs.unlinkSync(tmpPoster);
    const m = await sharp(path.join(OUT,'video',`work-${id}.webp`)).metadata();
    console.log('video',id,(fs.statSync(out).size/1048576).toFixed(2)+'MB',m.width+'x'+m.height);
  }
  // disinfection photos
  const ph = {'Дезинфекция/2eea8c34-ee86-43ea-a73b-576003cf2d57.jpg':'disinfekciya-transport','Дезинфекция/8724f429-5fe4-41e9-b39d-bbb04da0590e.jpg':'disinfekciya-tuman'};
  for (const [rel,name] of Object.entries(ph)) {
    const i = await sharp(path.join(SRC,rel)).resize({width:1200,withoutEnlargement:true}).webp({quality:76}).toFile(path.join(OUT,'images',name+'.webp'));
    console.log('photo',name,i.width+'x'+i.height);
  }
  // valid documents only: crop phone UI where needed
  const docs = [
    ['Лицензии/81fbfd87-b0a3-4acb-8777-890b45ad8745.jpg','deklaraciya-cipermetrin-250',null],
    ['Лицензии/Свидетельство.jpg','svidetelstvo-agran',null],
  ];
  for (const [rel,name,crop] of docs) {
    const i = await sharp(path.join(SRC,rel)).resize({width:1000,withoutEnlargement:true}).webp({quality:82}).toFile(path.join(OUT,'images/docs',name+'.webp'));
    console.log('doc',name,i.width+'x'+i.height);
  }
  // covers: keep as brand reference copies for OG / palette (resized)
  console.log('DONE');
})();
