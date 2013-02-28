#!/bin/bash
FULLPATH=$(dirname $(readlink -f "$0"))

ADDONMODE=
ADDONNAME=
if [ "$1" == "--addon" -o "$1" == "-a" ]
then
	ADDONMODE=1
	if [ -z $2 ]; then echo -e "ERROR: missing addon name\n\nrun_xgettext.sh -a <addonname>"; exit 1; fi
	ADDONNAME=$2
	if [ ! -d "$FULLPATH/../addon/$ADDONNAME" ]; then echo "ERROR: addon '$ADDONNAME' not found"; exit 2; fi
fi

if [ $ADDONMODE ]
then 
	cd "$FULLPATH/../addon/$ADDONNAME"
	mkdir -p "$FULLPATH/../addon/$ADDONNAME/lang/C"
	OUTFILE="$FULLPATH/../addon/$ADDONNAME/lang/C/messages.po"
	FINDSTARTDIR="."
	FINDOPTS=
else
	cd "$FULLPATH/../view/en/"
	OUTFILE="$FULLPATH/messages.po"
	FINDSTARTDIR="../../"
	# skip addon folder
	FINDOPTS="-wholename */addon -prune -o"
fi

F9KVERSION=$(sed -n "s/.*'FRIENDICA_VERSION'.*'\([0-9.]*\)'.*/\1/p" ../../boot.php);

echo "Friendica version $F9KVERSION"

OPTS=

#if [ "" != "$1" ]
#then
#	OUTFILE="$(readlink -f ${FULLPATH}/$1)"
#	if [ -e "$OUTFILE" ]
#	then
#		echo "join extracted strings"
#		OPTS="-j"
#	fi
#fi

KEYWORDS="-k -kt -ktt:1,2"

echo "extract strings to $OUTFILE.."
for f in $(find "$FINDSTARTDIR" $FINDOPTS -name "*.php" -type f)
do
	if [ ! -d "$f" ]
	then
		xgettext $KEYWORDS $OPTS -o "$OUTFILE" --from-code=UTF-8 "$f"
	fi
done

echo "setup base info.."
if [ $ADDONMODE ]
then 
	sed -i "s/SOME DESCRIPTIVE TITLE./ADDON $ADDONNAME/g" "$OUTFILE"
	sed -i "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER//g" "$OUTFILE"
	sed -i "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.//g" "$OUTFILE"
	sed -i "s/PACKAGE VERSION//g" "$OUTFILE"
	sed -i "s/PACKAGE/Friendica $ADDONNAME addon/g" "$OUTFILE"
	sed -i "s/CHARSET/UTF-8/g" "$OUTFILE"
	sed -i "s/^\"Plural-Forms/#\"Plural-Forms/g" "$OUTFILE"
else
	sed -i "s/SOME DESCRIPTIVE TITLE./FRIENDICA Distributed Social Network/g" "$OUTFILE"
	sed -i "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER/2010, 2011, 2012, 2013 the Friendica Project/g" "$OUTFILE"
	sed -i "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./Mike Macgirvin, 2010/g" "$OUTFILE"
	sed -i "s/PACKAGE VERSION/$F9KVERSION/g" "$OUTFILE"
	sed -i "s/PACKAGE/Friendica/g" "$OUTFILE"
	sed -i "s/CHARSET/UTF-8/g" "$OUTFILE"
	sed -i "s/^\"Plural-Forms/#\"Plural-Forms/g" "$OUTFILE"
fi

echo "done."
