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
var folder = SRC+"/assets/js/";
var walkSync = function(dir, filelist) {
  var needle = 'super-forms-build';
  var fs = fs || require('fs'),
  files = fs.readdirSync(dir);
  filelist = filelist || [];
  files.forEach(function(file) {
    if (fs.statSync(dir + file).isDirectory()) {
      filelist = walkSync(dir + file + '/', filelist);
    }else{
      if(path.extname(file)==='.js'){
        var slice_position = dir.indexOf(needle);
        filelist.push({
          path : dir.slice(slice_position+needle.length),
          name : file
        });
      }
    }
  });
  return filelist;
};
var files = walkSync(folder);
files.forEach(function (file, index) {
  var code = fs.readFileSync(ROOT+file.path+file.name, 'utf8');
  console.log('Uglifying '+file.name);
  fs.writeFileSync(ROOT+file.path+file.name, UglifyJS.minify(code, options).code, 'utf8');
  fs.writeFileSync(cacheFileName, JSON.stringify(options.nameCache), 'utf8');
});
