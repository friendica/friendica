#!/bin/bash

FULLPATH=$(dirname $(readlink -f "$0"))
cd "$FULLPATH/../view/en/"

F9KVERSION=$(sed -n "s/.*'FRIENDICA_VERSION'.*'\([0-9.]*\)'.*/\1/p" ../../boot.php);

echo "Friendica version $F9KVERSION"

OPTS=
OUTFILE="$FULLPATH/messages.po"
if [ "" != "$1" ]
then
	OUTFILE="$(readlink -f ${FULLPATH}/$1)"
	if [ -e "$OUTFILE" ]
	then
		echo "join extracted strings"
		OPTS="-j"
	fi
fi

KEYWORDS="-k -kt -ktt:1,2"

echo "extract strings to $OUTFILE.."
find ../../ -name "*.php" | xargs xgettext $KEYWORDS $OPTS -o "$OUTFILE" --from-code=UTF-8

echo "setup base info.."
sed -i "s/SOME DESCRIPTIVE TITLE./FRIENDICA Distributed Social Network/g" "$OUTFILE"
sed -i "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER/2010, 2011 the Friendica Project/g" "$OUTFILE"
sed -i "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./Mike Macgirvin, 2010/g" "$OUTFILE"
sed -i "s/PACKAGE VERSION/$F9KVERSION/g" "$OUTFILE"
sed -i "s/PACKAGE/Friendica/g" "$OUTFILE"
sed -i "s/CHARSET/UTF-8/g" "$OUTFILE"
sed -i "s/^\"Plural-Forms/#\"Plural-Forms/g" "$OUTFILE"


echo "done."
