#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
This script will collect the contributors to friendica and its translations from
  * the git log of the friendica core and addons repositories
  * the translated messages.po from core and the addons.
The collected names will be saved in /util/credits.txt which is also parsed from
yourfriendica.tld/credits.

The output is not perfect, so remember to open a fresh (re)created credits.txt file
in your fav editor to check for obvious mistakes and doubled entries.

Initially written by Tobias Diekershoff for the Friendica Project. Released under
the terms of the AGPL version 3 or later, same as Friendica.
"""

from sys import argv
import os, glob, subprocess

#  a list of names to not include, those people get into the list by other names
#  but they use different names on different systems and automatical mapping does
#  not work in some cases.
dontinclude = ['root', 'friendica', 'bavatar', 'tony baldwin', 'Taek', 'silke m',
               'leberwurscht', 'abinoam', 'fabrixxm', 'FULL NAME', 'Hauke Zuehl',
               'Michal Supler', 'michal_s', 'Manuel Pérez']


#  this script is in the /util sub-directory of the friendica installation
#  so the friendica path is the 0th argument of calling this script but we
#  need to remove the name of the file and the name of the directory
path = os.path.abspath(argv[0].split('util/make_credits.py')[0])
print('> base directory is assumed to be: '+path)
#  a place to store contributors
contributors = ['Andi Stadler']
#  get the contributors
print('> getting contributors to the friendica core repository')
p = subprocess.Popen(['git', 'shortlog', '--no-merges', '-s'],
                         stdout=subprocess.PIPE,
                         stderr=subprocess.STDOUT)
c = iter(p.stdout.readline, b'')
for i in c:
    name = i.decode().split('\t')[1].split('\n')[0]
    if not name in contributors and name not in dontinclude:
        contributors.append(name)
n1 = len(contributors)
print('  > found %d contributors' % n1)
#  get the contributors to the addons
os.chdir(path+'/addon')
#  get the contributors
print('> getting contributors to the addons')
p = subprocess.Popen(['git', 'shortlog', '--no-merges', '-s'],
                         stdout=subprocess.PIPE,
                         stderr=subprocess.STDOUT)
c = iter(p.stdout.readline, b'')
for i in c:
    name = i.decode().split('\t')[1].split('\n')[0]
    if not name in contributors and name not in dontinclude:
        contributors.append(name)
n2 = len(contributors)
print('  > found %d new contributors' % (n2-n1))
print('> total of %d contributors to the repositories of friendica' % n2)
os.chdir(path)
#  get the translators
print('> getting translators')
intrans = False
for f in glob.glob(path+'/view/*/messages.po'):
    i = open(f, 'r')
    l = i.readlines()
    i.close()
    for ll in l:
        if intrans and ll.strip()=='':
            intrans = False;
        if intrans and ll[0]=='#':
            name = ll.split('# ')[1].split(',')[0].split(' <')[0]
            if not name in contributors and name not in dontinclude:
                contributors.append(name)
        if "# Translators:" in ll:
            intrans = True
#  get the translators from the addons
for f in glob.glob(path+'/addon/*/lang/*/messages.po'):
    i = open(f, 'r')
    l = i.readlines()
    i.close()
    for ll in l:
        if intrans and ll.strip()=='':
            intrans = False;
        if intrans and ll[0]=='#':
            name = ll.split('# ')[1].split(',')[0].split(' <')[0]
            if not name in contributors and name not in dontinclude:
                contributors.append(name)
        if "# Translators:" in ll:
            intrans = True
#  done with the translators

n3 = len(contributors)
print('  > found %d translators' % (n3-n2))
print('> found a total of %d contributors and translators' % n3)
contributors.sort(key=str.lower)

f = open(path+'/util/credits.txt', 'w')
f.write("\n".join(contributors))
f.close()
print('> list saved to util/credits.txt')
