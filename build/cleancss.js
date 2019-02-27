const CleanCSS = require('clean-css')
const path = require('path')
const fs = require('fs')
const ROOT = path.resolve(__dirname, '..', '')
const SRC = ROOT+'/src'
const DIST = ROOT+'/dist'
const ASSETS = '/dist'
var folder = DIST+"/assets/css/";
var options = { 
  compatibility: 'ie9' 
};
var walkSync = function(dir, filelist) {
  var fs = fs || require('fs'),
  files = fs.readdirSync(dir);
  filelist = filelist || [];
  files.forEach(function(file) {
    if (fs.statSync(dir + file).isDirectory()) {
      filelist = walkSync(dir + file + '/', filelist);
    }else{
      if(path.extname(file)==='.css'){
        filelist.push({
          path : dir,
          name : file
        });
      }
    }
  });
  return filelist;
};
var files = walkSync(folder);
files.forEach(function (file, index) {
  var code = fs.readFileSync(file.path+file.name, 'utf8');
  var output = new CleanCSS(options).minify(code);
  console.log('Cleaning up '+file.name);
  fs.writeFileSync(file.path+file.name, output.styles, 'utf8');
});