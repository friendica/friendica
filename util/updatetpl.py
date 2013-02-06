#!/usr/bin/python
#
# Script to update Smarty template files from all internal templates
# Copyright 2013 Zach Prezkuta
# Licensed under GPL v3


import os
import sys, getopt
import subprocess


def help(pname):
	print "\nUsage:"
	print "\t" + pname + " -h\n\n\t\t\tShow this help screen\n"
	print "\t" + pname + " -p directory\n\n\t\t\tConvert all .tpl files in top-level\n\t\t\tFriendica directory to Smarty templates\n"
	print "\t" + pname + "\n\n\t\t\tInteractive mode\n"



#
# Main script
#

path = ''

try:
	opts, args = getopt.getopt(sys.argv[1:], "hp:")
	for opt, arg in opts:
		if opt == '-h':
			help(sys.argv[0])
			sys.exit()
		elif opt == '-p':
			path = arg
except getopt.GetoptError:
	help(sys.argv[0])
	sys.exit(2)

if path == '':
	path = raw_input('Path to top-level Friendica directory: ')

if path[-1:] != '/':
	path = path + '/'

tplpaths = ['view/']
names = os.listdir(path + 'view/')
for name in names:
	if os.path.isdir(path + 'view/' + name):
		if name != 'smarty3' and name != 'theme':
			tplpaths.append('view/' + name + '/')

names = os.listdir(path + 'view/theme/')
for name in names:
	if os.path.isdir(path + 'view/theme/' + name):
		tplpaths.append('view/theme/' + name + '/')

fnull = open(os.devnull, "w")

for tplpath in tplpaths:
	print "Converting " + path + tplpath
	subprocess.call(['python', path + 'util/friendica-to-smarty-tpl.py', '-p', path + tplpath], stdout = fnull)

fnull.close()

