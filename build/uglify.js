const UglifyJS = require('uglify-js')
const path = require('path')
const fs = require('fs')
const ROOT = path.resolve(__dirname, '..', '')
const SRC = ROOT+'/src'
const DIST = ROOT+'/dist'
const ASSETS = '/dist'
var cacheFileName = ROOT+"/build/tmp/cache.json";
var options = {
    toplevel: false,
    mangle: { 
      keep_fnames: true
    },
    nameCache: JSON.parse(fs.readFileSync(cacheFileName, "utf8")),
    compress: {
        passes: 1
    },
    output: {
        beautify: false
    }
};
var folder = DIST+"/assets/js/";
var walkSync = function(dir, filelist) {
  var fs = fs || require('fs'),
  files = fs.readdirSync(dir);
  filelist = filelist || [];
  files.forEach(function(file) {
    if (fs.statSync(dir + file).isDirectory()) {
      filelist = walkSync(dir + file + '/', filelist);
    }else{
      if(path.extname(file)==='.js'){
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
  console.log('Uglifying '+file.name);
  fs.writeFileSync(file.path+file.name, UglifyJS.minify(code, options).code, 'utf8');
  fs.writeFileSync(cacheFileName, JSON.stringify(options.nameCache), 'utf8');
});
