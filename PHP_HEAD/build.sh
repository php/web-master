#!/bin/sh

export PHP_VER=PHP_HEAD

export DO_CVS_CLEAN=1
export FROM_SCRATCH=1
export BUILD_SOURCE=1
export USE_VALGRIND=1
export RM_ALL_FIRST=1

export BASE_WEB=/local/Web/sites/php-gcov-web
export BASE_COV=/p2/var/php-gcov
export BASE_PHP=$BASE_COV/$PHP_VER

export PHP_SRC=$BASE_PHP/php-src
export PHP_MAK=$BASE_PHP/build
export PHP_BIN=$BASE_PHP/bin
export PHP_ETC=$BASE_PHP/bin/etc
export PHP_HTM=$BASE_PHP/html
export PHP_TMP=$BASE_PHP/tmp
export PHP_TST=$PHP_HTM/tests
export PHP_WEB=$BASE_WEB/$PHP_VER

export CCACHE_DISABLE=1
export CC=/usr/bin/gcc
export CFLAGS='-Wall -Wno-strict-aliasing -ggdb'
export LTP_GENHTML="genhtml --html-prolog ${BASE_WEB}/gcov_prolog.inc --html-epilog ${BASE_WEB}/gcov_epilog.inc --html-extension php --prefix ${BASE_PHP}"

export PDO_PGSQL_TEST_DSN="pgsql:host=localhost port=5432 dbname=test_${PHP_VER}"
export PDO_FIREBIRD_TEST_DSN="firebird:dbname=/var/php-gcov/${PHP_VER}/tmp/firebird_test.db"
export PDO_FIREBIRD_TEST_USER="SYSDBA"
#export PDO_FIREBIRD_TEST_PASS="***"

if test "$FROM_SCRATCH" = "1"; then
	for i in $PHP_MAK; do
		test -d $i && rm -rf $i
		mkdir -p $i
	done
fi

if test "$BUILD_SOURCE" = "1"; then
	for i in $PHP_BIN $PHP_ETC; do
		test -d $i && rm -rf $i
		mkdir -p $i
	done
fi

if test "$RM_ALL_FIRST" = "1"; then
	for i in $PHP_HTM $PHP_TMP $PHP_TST; do
		test -d $i && rm -rf $i
		mkdir -p $i
		chmod ugo+rwx $i
	done
fi

if test "$DO_CVS_CLEAN" = "1"; then
	cd $PHP_SRC
	./cvsclean || exit 1
	cvs up
	./buildconf --force || exit 1
fi

if test "$FROM_SCRATCH" = "1"; then
	cd $PHP_MAK

	$PHP_SRC/configure \
	  --prefix=$PHP_BIN \
	  --with-config-file-path=$PHP_ETC \
	  --disable-maintainer-zts \
	  --disable-inline-optimization \
	  --disable-safe-mode \
	  --enable-memory-limit \
	  --disable-magic-quotes \
	  --enable-gcov \
	  --enable-cli \
	  --disable-cgi \
	  --enable-all \
	  --with-db4 \
	  --without-fbsql \
	  --without-fdftk \
	  --without-hwapi \
	  --with-imap-ssl \
	  --without-informix \
	  --with-interbase \
	  --with-kerberos \
	  --without-libedit \
	  --without-libexpat-dir \
	  --disable-mbregex \
	  --without-ming \
	  --without-msession \
	  --without-msql \
	  --without-mssql \
	  --with-mysql \
	  --with-mysqli \
	  --with-openssl \
	  --without-oci8 \
	  --with-pcre-regex \
	  --without-oracle \
	  --without-pdo-dblib \
	  --with-pdo-firebird \
	  --with-pdo-mysql \
	  --without-pdo-odbc \
	  --without-pdo-oci \
	  --with-pdo-sqlite \
	  --without-readline \
	  --without-recode \
	  --without-snmp \
	  --with-sqlite=/usr \
	  --without-sybase \
	  --without-sybase-ct \
	  --with-xsl \
	  || exit 1
fi	

if test "$BUILD_SOURCE" = "1"; then
	cd $PHP_MAK
	mkdir -p ext/sqlite/libsqlite/src
	make || exit 1
	make install || exit 1
fi

export TEST_VALGRIND_OPT=
test "$USE_VALGRIND" = "1" && export TEST_VALGRIND_OPT="-m "

export TEST_PHP_EXECUTABLE=$PHP_BIN/bin/php.bbg
export TEST_PHP_SRCDIR=$PHP_TST
export TEST_PHP_LOG_FORMAT=LEOD
export TEST_PHP_DETAILED=0
export TEST_PHP_USER=
export TEST_PHP_ARGS="${TEST_VALGRIND_OPT}-U -n -q --html $PHP_HTM/run-tests.html.inc -s $PHP_HTM/run-tests.gcov.log -w $PHP_HTM/run-tests.fail.lst --temp-source $PHP_SRC --temp-target $PHP_TST --temp-urlbase /$PHP_VER/tests $PHP_SRC"
export REPORT_EXIT_STATUS=0
export NO_INTERACTION=1

cd $PHP_MAK
make lcov-html || exit 1

test -d $PHP_WEB || mkdir $PHP_WEB
test -d $PHP_HTM/lcov || mkdir $PHP_HTM/lcov
cp -r $PHP_MAK/lcov_html/* $PHP_HTM/lcov
if test -d $PHP_WEB; then
	cp -r $PHP_HTM/* $PHP_WEB
	chmod -R ugo+r $PHP_WEB
fi
