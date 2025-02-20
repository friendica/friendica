#!/bin/sh

# SPDX-FileCopyrightText: 2010 - 2024 the Friendica project
#
# SPDX-License-Identifier: CC0-1.0

set -e

# Custom function to handle pipefail behavior
pipefail() {
    local cmd="$1"
    shift
    { eval "$cmd"; } || exit 1
}

resolve() {
	if [ "$(uname)" = "Darwin" ]
	then
		realpath "$1"
	else
		readlink -f "$1"
	fi
}

FULLPATH=$(dirname "$(resolve "$0")")

if [ "$1" = "--help" ] || [ "$1" = "-h" ]
then
	echo "$(basename "$(resolve "$0")") [options]"
	echo
	echo "-a | --addon <name>	extract strings from addon 'name'"
	echo "-s | --single			single addon mode: extract string from current folder"
	exit
fi

MODE='default'
ADDONNAME=
if [ "$1" = "--addon" ] || [ "$1" = "-a" ]
then
	MODE='addon'
	if [ -z "$2" ]; then echo "ERROR: missing addon name\n\nrun_xgettext.sh -a <addonname>"; exit 1; fi
	ADDONNAME=$2
	if [ ! -d "$FULLPATH/../addon/$ADDONNAME" ]; then echo "ERROR: addon '$ADDONNAME' not found"; exit 2; fi
fi

if [ "$1" = "--single" ] || [ "$1" = "-s" ]
then
	MODE='single'
fi

case "$MODE" in
	'addon')
		cd "$FULLPATH/../addon/$ADDONNAME"
		mkdir -p "$FULLPATH/../addon/$ADDONNAME/lang/C"
		OUTFILE="$FULLPATH/../addon/$ADDONNAME/lang/C/messages.po"
		FINDSTARTDIR="."
		FINDOPTS="-path ./vendor -prune -or"
	;;
	'single')
		FULLPATH=$PWD
		ADDONNAME=$(basename "$FULLPATH")
		mkdir -p "$FULLPATH/lang/C"
		OUTFILE="$FULLPATH/lang/C/messages.po"
		FINDSTARTDIR="."
		FINDOPTS="-path ./vendor -prune -or"
		echo "Extract strings for single addon '$ADDONNAME'"
	;;
	'default')
		cd "$FULLPATH/.."
		OUTFILE="$FULLPATH/../view/lang/C/messages.po"
		FINDSTARTDIR="."
		# skip addon folder
		FINDOPTS="( -path ./addon -or -path ./addons -or -path ./addons-extra -or -path ./tests -or -path ./view/lang -or -path ./view/smarty3 -or -path ./vendor  -or -path ./local  -or -path ./avatar -or -path ./proxy ) -prune -or"
		
		F9KVERSION=$(cat ./VERSION);
		echo "Friendica version $F9KVERSION"
	;;
esac

KEYWORDS="-k -kt -ktt:1,2"

echo "Extract strings to $OUTFILE.."
[ -f "$OUTFILE" ] && rm "$OUTFILE"; touch "$OUTFILE"

# shellcheck disable=SC2086  # $FINDOPTS is meant to be split
find_result=$(find "$FINDSTARTDIR" $FINDOPTS -name "*.php" -type f | LC_ALL=C sort -s)

total_files=$(echo "${find_result}" | wc -l)

count=1
for file in $find_result
do
	printf "                                            \r"
	printf "Reading file %d/%d..." "$count" "$total_files"

	# On Windows, find still outputs the name of pruned folders
	if [ ! -d "$file" ]
	then
		# shellcheck disable=SC2086  # $KEYWORDS is meant to be split
		xgettext $KEYWORDS --no-wrap -j -o "$OUTFILE" --from-code=UTF-8 "$file" || exit 1
		sed -i.bkp "s/CHARSET/UTF-8/g" "$OUTFILE"
	fi
	count=$((count + 1))
done

echo "Interpolate metadata.."

sed -i.bkp "s/^\"Plural-Forms.*$//g" "$OUTFILE"

case "$MODE" in
	'addon'|'single')
		sed -i.bkp "s/SOME DESCRIPTIVE TITLE./ADDON $ADDONNAME/g" "$OUTFILE"
		sed -i.bkp "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER//g" "$OUTFILE"
		sed -i.bkp "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.//g" "$OUTFILE"
		sed -i.bkp "s/PACKAGE VERSION//g" "$OUTFILE"
		sed -i.bkp "s/PACKAGE/Friendica $ADDONNAME addon/g" "$OUTFILE"
	;;
	'default')
		sed -i.bkp "s/SOME DESCRIPTIVE TITLE./FRIENDICA Distributed Social Network/g" "$OUTFILE"
		sed -i.bkp "s/YEAR THE PACKAGE'S COPYRIGHT HOLDER/2010-$(date +%Y), the Friendica project/g" "$OUTFILE"
		sed -i.bkp "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./Mike Macgirvin, 2010/g" "$OUTFILE"
		sed -i.bkp "s/PACKAGE VERSION/$F9KVERSION/g" "$OUTFILE"
		sed -i.bkp "s/PACKAGE/Friendica/g" "$OUTFILE"
	;;
esac

if [ -n "$1" ] && [ "$MODE" = "default" ]
then
	UPDATEFILE="$(resolve "${FULLPATH}/$1")"
	echo "Merging new strings to $UPDATEFILE.."
	msgmerge -U "$OUTFILE" "$UPDATEFILE"
fi

[ -f "$OUTFILE.bkp" ] && rm "$OUTFILE.bkp" 

echo "Done."
