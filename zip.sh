#!/bin/bash
set -eu
dist="dist/"
for folder in `find dist/. -maxdepth 1 -mindepth 1 -type d`; do
    # printf $((basename $folder));
    folder_name=$dist$(basename "$folder" .deb)
    zip_file_name=$dist$(basename "$folder" .deb).zip
    # printf "$folder_name\n";
    # printf "$zip_file_name\n";
    # printf "cp -r $dist$folder_name $workdir\n";
	unset workdir
	onexit() {
	  if [ -n ${workdir-} ]; then
	    rm -rf "$workdir"
	  fi
	}
	trap onexit EXIT
	workdir=$(mktemp -d gitzip.XXXXXX)
	cp -r "$folder_name" "$workdir"
	pushd "$workdir"
	git init
	git add .
	git commit -m "commit for zip"
	popd
	git archive --format=zip -o "$zip_file_name" --remote="$workdir" HEAD
done

# // "zip": "find dist/. -maxdepth 1 -mindepth 1 -type d -exec zip.sh %f.zip dist/%f ;",
# // $ find dist/. -maxdepth 1 -mindepth 1 -type d -printf '%f.zip : %f\n'
# // $ find dist/. -maxdepth 1 -mindepth 1 -type d echo '%f.zip : %f\n'
# // $ find dist/. -maxdepth 1 -mindepth 1 -type d -exec bash zip.sh 'dist/%f.zip' 'dist/%f' {} +
# // $ find dist/. -maxdepth 1 -mindepth 1 -type d -exec bash zip.sh 'dist/basename.zip' 'dist/basename' {} +
# // $ for f in `find dist/. -maxdepth 1 -mindepth 1 -type d`; do basename $f; done;
# // $ for f in `find dist/. -maxdepth 1 -mindepth 1 -type d`; do basename $f; done;
# // $ for f in `find dist/. -maxdepth 1 -mindepth 1 -type d`; do echo $f; done;
# // $ bash zip.sh 'dist/super-forms-calculator.zip' 'dist/super-forms-calculator'
