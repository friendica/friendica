#!/bin/bash
FULLPATH=$(dirname $(readlink -f "$0"))

if [ "$1" == "--help" -o "$1" == "-h" ]
then
	echo "$(basename $(readlink -f "$0")) [options]"
	echo
	echo "-a | --addon <name>	extract strings from addon 'name'"
	echo "-s | --single				single addon mode: extract string from current folder"
	exit
fi

MODE='default'
ADDONNAME=
if [ "$1" == "--addon" -o "$1" == "-a" ]
then
	MODE='addon'
	if [ -z $2 ]; then echo -e "ERROR: missing addon name\n\nrun_xgettext.sh -a <addonname>"; exit 1; fi
	ADDONNAME=$2
	if [ ! -d "$FULLPATH/../addon/$ADDONNAME" ]; then echo "ERROR: addon '$ADDONNAME' not found"; exit 2; fi
fi

if [ "$1" == "--single" -o "$1" == "-s" ]
then
	MODE='single'
fi


case "$MODE" in
	'addon')
		cd "$FULLPATH/../addon/$ADDONNAME"
		mkdir -p "$FULLPATH/../addon/$ADDONNAME/lang/C"
		OUTFILE="$FULLPATH/../addon/$ADDONNAME/lang/C/messages.po"
		FINDSTARTDIR="."
		FINDOPTS=
	;;
	'single')
		FULLPATH=$PWD
		ADDONNAME=$(basename $FULLPATH)
		mkdir -p "$FULLPATH/lang/C"
		OUTFILE="$FULLPATH/lang/C/messages.po"
		FINDSTARTDIR="."
		FINDOPTS=		
		echo "Extract strings for single addon '$ADDONNAME'"
	;;
	'default')
		cd "$FULLPATH/.."
		OUTFILE="$FULLPATH/../util/messages.po"
		FINDSTARTDIR="."
		# skip addon folder
		FINDOPTS="( -wholename */addon -or -wholename */addons-extra -or -wholename */smarty3 ) -prune -o"
		
		F9KVERSION=$(sed -n "s/.*'FRIENDICA_VERSION'.*'\([0-9.]*\)'.*/\1/p" ./boot.php);
		echo "Friendica version $F9KVERSION"
	;;
esac


KEYWORDS="-k -kt -ktt:1,2"

echo "extract strings to $OUTFILE.."
rm "$OUTFILE"; touch "$OUTFILE"
for f in $(find "$FINDSTARTDIR" $FINDOPTS -name "*.php" -type f)
do
	if [ ! -d "$f" ]
	then
		xgettext $KEYWORDS -j -o "$OUTFILE" --from-code=UTF-8 "$f"
		sed -i "s/CHARSET/UTF-8/g" "$OUTFILE"
	fi
done

echo "setup base info.."
case "$MODE" in 
	'addon'|'single')
		sed -i "s/SOME DESCRIPTIVE TITLE./ADDON $ADDONNAME/g" "$OUTFILE"
		sed -i "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER//g" "$OUTFILE"
		sed -i "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.//g" "$OUTFILE"
		sed -i "s/PACKAGE VERSION//g" "$OUTFILE"
		sed -i "s/PACKAGE/Friendica $ADDONNAME addon/g" "$OUTFILE"
		sed -i "s/CHARSET/UTF-8/g" "$OUTFILE"
		sed -i "s/^\"Plural-Forms.*$//g" "$OUTFILE"
	;;
	'default')
		sed -i "s/SOME DESCRIPTIVE TITLE./FRIENDICA Distributed Social Network/g" "$OUTFILE"
		sed -i "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER/2010, 2011, 2012, 2013 the Friendica Project/g" "$OUTFILE"
		sed -i "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./Mike Macgirvin, 2010/g" "$OUTFILE"
		sed -i "s/PACKAGE VERSION/$F9KVERSION/g" "$OUTFILE"
		sed -i "s/PACKAGE/Friendica/g" "$OUTFILE"
		sed -i "s/CHARSET/UTF-8/g" "$OUTFILE"
		sed -i "s/^\"Plural-Forms.*$//g" "$OUTFILE"
	;;
esac

if [ "" != "$1" -a "$MODE" == "default" ]
then
	UPDATEFILE="$(readlink -f ${FULLPATH}/$1)"
	echo "merging new strings to $UPDATEFILE.."
	msgmerge -U $OUTFILE $UPDATEFILE
fi

echo "done."
