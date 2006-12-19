#!/bin/sh

#  +----------------------------------------------------------------------+
#  | PHP QA GCOV Website                                                  |
#  +----------------------------------------------------------------------+
#  | Copyright (c) 2005-2006 The PHP Group                                |
#  +----------------------------------------------------------------------+
#  | This source file is subject to version 3.01 of the PHP license,      |
#  | that is bundled with this package in the file LICENSE, and is        |
#  | available through the world-wide-web at the following url:           |
#  | http://www.php.net/license/3_01.txt                                  |
#  | If you did not receive a copy of the PHP license and are unable to   |
#  | obtain it through the world-wide-web, please send a note to          |
#  | license@php.net so we can mail you a copy immediately.               |
#  +----------------------------------------------------------------------+
#  | Author: Daniel Pronych <pronych@php.net>                             |
#  |         Nuno Lopes <nlopess@php.net>                                 |
#  +----------------------------------------------------------------------+

#   $Id: cron.sh,v 1.2 2006-12-19 15:52:09 nlopess Exp $

source ./config.sh
export LC_ALL=C
export CCACHE_DISABLE=1

# Called either on error or successful completion
remove_pid_file()
{
	rm -f "$PIDFILE"
}
trap remove_pid_file EXIT


# file that contains the PHP version tags
FILENAME=tags.inc

WORKDIR=`dirname "$0"`
echo "$WORKDIR" | grep -q '^/' || WORKDIR="`pwd`/$WORKDIR"  # get absolute path

# make genhtml use our header/footer
export LTP_GENHTML="genhtml --html-prolog ${WORKDIR}/lcov_prolog.inc --html-epilog ${WORKDIR}/lcov_epilog.inc --html-extension php"

# set up a one dimensional array to store all php version information
declare -a TAGS_ARRAY
TAGS_ARRAY=( `cat "$FILENAME"` )

# Calculate how many elements there are in a php version array
TAGS_COUNT=${#TAGS_ARRAY[@]}

BUILT_SOME=0

# Check for a build version passed to the script
if [ $# -eq 1 ]; then
	BUILD=$1
else
	BUILD="_all_"
fi

# loop through each PHP version and perform the required builds
for (( i = 0 ; i < $TAGS_COUNT ; i += 1 ))
do
	PHPTAG=${TAGS_ARRAY[i]}

	# Build all has no exceptions
	if [ $BUILD = "_all_" ]; then
		BUILD_VERSION=1
	else
		if [ $BUILD = $PHPTAG ]; then
			BUILD_VERSION=1
		else
			BUILD_VERSION=0
		fi
	fi

	# If this version should be built
	if [ $BUILD_VERSION = 1 ]; then

		BUILT_SOME=1
		BEGIN=`date +%s`

		CVSTAG=${PHPTAG}
		OUTDIR=${OUTROOT}/${PHPTAG}
		PHPSRC=${PHPROOT}/${PHPTAG}
		TMPDIR=${PHPROOT}/tmp/${PHPTAG}
		PIDFILE=${OUTDIR}/build.pid

		if [ "${CVSTAG}" = "PHP_HEAD" ]; then
			CVSTAG="HEAD"
		fi

		mkdir -p $OUTDIR
		mkdir -p $TMPDIR

		echo $$ > ${PIDFILE}

		cd ${PHPROOT}
		if [ -d ${PHPTAG} ]; then
			cd ${PHPTAG}
			cvs -q up -Pd
			# CVS doesn't update the Zend dir automatically
			cd Zend
			cvs -q up -Pd
			cd ..
		else
			cvs -q -d ${CVSROOT} co -d ${PHPTAG} -r ${CVSTAG} php-src
			cd ${PHPTAG}
		fi
		./cvsclean
		./buildconf --force > /dev/null

		if [ -x ./config.nice ]; then
			./config.nice > /dev/null
		else
			# try to run with the default options
			./configure > /dev/null
		fi

		if ( make > /dev/null 2> ${TMPDIR}/php_build.log ); then

			MAKESTATUS=pass

			TEST_PHP_ARGS="-U -n -q --keep-all"

			# only run valgrind testing if it is available
			if (valgrind --version >/dev/null 2>&1 && test "$VALGRIND" ); then
				TEST_PHP_ARGS="${TEST_PHP_ARGS} -m"
			fi

			export TEST_PHP_ARGS

			# test for lcov support
			if ( grep lcov Makefile >/dev/null 2>&1 ); then
				make lcov > ${TMPDIR}/php_test.log
				rm -fr ${OUTDIR}/lcov_html
				mv lcov_html ${OUTDIR}
			else
				make test > ${TMPDIR}/php_test.log
			fi

			echo "make successful: ${PHPTAG}"
		else
			MAKESTATUS=fail
			echo "make failed"
		fi # End build failure or success

		BUILD_TIME=$[`date +%s` - ${BEGIN}]

		php ${WORKDIR}/cron.php ${TMPDIR} ${OUTDIR} ${PHPSRC} ${MAKESTATUS} ${PHPTAG} ${BUILD_TIME}

		remove_pid_file

	fi # End verify build PHP version
done


# display an error if the tag doesn't exist
if [ $BUILT_SOME = 0 ]; then
	echo "Invalid tag specified: '$BUILD'"
	echo
	exit 1
fi
