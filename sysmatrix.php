<?php
require_once 'functions.inc';
head("machine inventory");
?>
<style>
p,td,body {
  font-size: 14px;
}

</style>
<p>php.net is supported by a number of machines provided by a number
of generous sponsors. this is a basic inventory of those machines and
what services they provide.</p>

<p> This matrix is intended to try and demonstrate where our services are 
distributed in an attempt to better improve them.</p>

<table width="90%" border="1">
  <tr> 
    <td>machine names (.php.net)</td>
    <td>cpu</td>
    <td>mem</td>
    <td>free</td>
    <td>top</td>
    <td>disk free/used</td>
    <td>uptime</td>
    <td>current use</td>
    <td>proposed use</td>
  </tr>
  <tr> 
    <td><a href="#rs1">rs1.php.net</a></td>
    <td>p3/1GHz</td>
    <td>1GB</td>
  <td><a href="#rs1free">#</a></td>
    <td><a href="#rs1top">#</a></td>
    <td><a href="#rs1df">#</a></td>
    <td><a href="#rs1up">#</a></td>
    <td>www</td>
    <td>www</td>
  </tr>
  <tr> 
    <td><a href="#pair1">pair1.php.net</a></td>
    <td>dual p3/1GHz</td>
    <td>512MB</td>
  <td><a href="#pair1free">#</a></td>
    <td><a href="#pair1top">#</a></td>
    <td><a href="#pair1df">#</a></td>
    <td><a href="#pair1up">#</a></td>
    <td>lists/cvs/pear/lxr/bonsai/news</td>
    <td>cvs/db/master/cvsweb (?)</td>
  </tr>
  <tr> 
    <td><a href="#pair2">pair2.php.net</a></td>
    <td>p3/566</td>
    <td>128MB</td>
   <td><a href="#pair2free">#</a></td>
    <td><a href="#pair2top">#</a></td>
    <td><a href="#pair2df">#</a></td>
    <td><a href="#pair2up">#</a></td>
    <td>mx</td>
    <td>lists/mx/news</td>
  </tr>
  <tr> 
    <td>pair3.php.net</td>
    <td>???</td>
    <td>???</td>
    <td>???</td>
    <td>&nbsp;</td>
    <td>???</td>
    <td>???</td>
    <td>n/a</td>
    <td>pear</td>
  </tr>
  <tr> 
    <td><a href="#rn1">rn1.php.net</a></td>
    <td>p3/1.1GHz</td>
    <td>1GB</td>
    <td><a href="#rn1free">#</a></td>
    <td><a href="#rn1top">#</a></td>
    <td><a href="#rn1df">#</a></td>
    <td><a href="#rn1up">#</a></td>
    <td>rsync/snaps/build</td>
    <td>rsync/snaps/lxr/bonsai/qa/<br>
      bugs/gtk/smarty</td>
  </tr>
  <tr> 
    <td>rn2.php.net</td>
    <td>???</td>
    <td>???</td>
    <td>???</td>
    <td>&nbsp;</td>
    <td>???</td>
    <td>???</td>
    <td>n/a</td>
    <td>rsync/snaps/lxr/bonsai/qa/<br>
      bugs/gtk/smarty</td>
  </tr>
  <tr> 
    <td><a href="#rack1">rack1.php.net</a></td>
    <td>dual p3/650</td>
    <td>1GB</td>
  <td><a href="#rack1free">#</a></td>
    <td><a href="#rack1top">#</a></td>
    <td><a href="#rack1df">#</a></td>
    <td><a href="#rack1up">#</a></td>
    <td>axfr/qa/bugs/gtk/master/smarty/db</td>
    <td>axfr/build</td>
  </tr>
  <tr> 
    <td>nyc-box (derick)</td>
    <td>???</td>
    <td>???</td>
    <td>???</td>
    <td>&nbsp;</td>
    <td>???</td>
    <td>???</td>
    <td>n/a</td>
    <td>fast file server (thttpd?)</td>
  </tr>
  <tr> 
    <td>simplicato.com (james)</td>
    <td>sim1.php.net (?)</td>
    <td>???</td>
    <td>???</td>
    <td>&nbsp;</td>
    <td>???</td>
    <td>???</td>
    <td>n/a</td>
    <td>backup?</td>
  </tr>
  <tr> 
    <td>positive internet box</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>n/a</td>
    <td>???</td>
  </tr>
</table>
<pre>

<a name="rn1">rn1</a>:

<a name="rn1top">top</a>:
 
7:12pm up 86 days, 3:57, 1 user, load average: 1.74, 1.19, 0.80
  84 processes: 80 sleeping, 4 running, 0 zombie, 0 stopped
  CPU states: 0.1% user, 0.5% system, 0.0% nice, 0.0% idle
  Mem: 1031232K av, 883792K used, 147440K free, 0K shrd, 126332K buff
  Swap: 2097112K av, 18084K used, 2079028K free 607220K cached

<a name="rn1free">:~:$ free</a>

  total used free shared buffers cached
  Mem: 1031232 954856 76376 0 117172 656236
  -/+ buffers/cache: 181448 849784
  Swap: 2097112 18084 2079028

<a name="rn1df">:~:$ df -h</a>

  Filesystem Size Used Avail Use% Mounted on
  /dev/cciss/c0d0p3 165G 4.6G 151G 3% /
  /dev/cciss/c0d0p1 50M 14M 34M 29% /boot
  none 503M 0 503M 0% /dev/shm

<a name="rn1up">:~:$ uptime</a>

  7:04pm up 86 days, 3:49, 1 user, load average: 0.53, 0.18, 0.18

<a name="rs1">rs1:</a>

<a name="rs1top">top:</a>

 10:01pm up 176 days, 7:25, 3 users, load average: 1.11, 1.18, 1.33
  103 processes: 98 sleeping, 5 running, 0 zombie, 0 stopped
  CPU states: 0.2% user, 0.2% system, 0.0% nice, 0.2% idle
  Mem: 1034468K av, 993056K used, 41412K free, 0K shrd, 28636K buff
  Swap: 1024120K av, 224368K used, 799752K free 381108K cached

<a name="rs1free">:~:&gt; free </a>

  total used free shared buffers cached
  Mem: 1034468 1018068 16400 0 27488 375520
  -/+ buffers/cache: 615060 419408
  Swap: 1024120 224368 799752

<a name="rs1df">:~:&gt; df -h</a>

Filesystem            Size  Used Avail Use% Mounted on
/dev/hde3              36G   17G   16G  51% /
/dev/hde1             145M  8.1M  129M   6% /boot
none                  505M     0  505M   0% /dev/shm

<a name="rs1up">:~:&gt; uptime</a>

9:57pm up 176 days, 7:21, 3 users, load average: 1.04, 1.29, 1.40

<a name="pair1">pair1:</a>

<a name="pair1top">top:</a>
<a name="pair1free"></a>
last pid: 50470; load averages: 0.29, 0.42, 0.34 up 33+09:41:34 22:06:59
  119 processes: 1 running, 118 sleeping
  CPU states: 4.9% user, 0.0% nice, 8.8% system, 0.4% interrupt, 86.0% idle
  Mem: 202M Active, 156M Inact, 117M Wired, 25M Cache, 61M Buf, 1056K Free
  Swap: 1024M Total, 39M Used, 985M Free, 3% Inuse

<a name="pair1df">~:&gt; df -h</a>

  Filesystem Size Used Avail Capacity Mounted on
  /dev/ad0s1a 992M 63M 850M 7% /
  /dev/ad0s1f 24G 8.9G 13G 40% /usr
  /dev/ad0s1e 1.9G 763M 1.0G 42% /var
  procfs 4.0K 4.0K 0B 100% /proc
  /dev/md0c 96M 6.2M 82M 7% /ftmp

<a name="pair1up">~:&gt; uptime</a>

  10:07PM up 33 days, 9:41, 1 user, load averages: 0.32, 0.41, 0.34

<a name="pair2">pair2:</a>

<a name="pair2top">top:</a>
<a name="pair2free"></a>
last pid: 32307; load averages: 0.08, 0.23, 0.38 up 196+14:56:59 22:09:07
  36 processes: 1 running, 34 sleeping, 1 zombie
  CPU states: 0.4% user, 0.0% nice, 0.0% system, 0.0% interrupt, 99.6% idle
  Mem: 28M Active, 26M Inact, 27M Wired, 4744K Cache, 22M Buf, 37M Free
  Swap: 524M Total, 13M Used, 510M Free, 2% Inuse

<a name="pair2df">:~:&gt; df -h</a>

  Filesystem Size Used Avail Capacity Mounted on
  /dev/ad0s1a 97M 42M 47M 48% /
  /dev/ad0s1f 27G 6.5G 18G 26% /usr
  /dev/ad0s1e 19M 11M 6.7M 62% /var
  procfs 4.0K 4.0K 0B 100% /proc

<a name="pair2up">:~:&gt; uptime</a>

  10:09PM up 196 days, 14:57, 1 user, load averages: 0.06, 0.22, 0.37

<a name="rack1">rack1:</a>

<a name="rack1top">top:</a>

 9:09pm up 121 days, 5:06, 1 user, load average: 0.14, 0.07, 0.02
  97 processes: 96 sleeping, 1 running, 0 zombie, 0 stopped
  CPU states: 0.3% user, 3.4% system, 0.0% nice, 96.1% idle
  Mem: 1030940K av, 973384K used, 57556K free, 0K shrd, 28256K buff
  Swap: 1048552K av, 394672K used, 653880K free 682084K cached

<a name="rack1free">[imajes@rack1 imajes]$ free</a>

  total used free shared buffers cached
  Mem: 1030940 973592 57348 0 28256 682088
  -/+ buffers/cache: 263248 767692
  Swap: 1048552 394672 653880

<a name="rack1df">[imajes@rack1 imajes]$ df -h</a>

  Filesystem Size Used Avail Use% Mounted on
  /dev/sda5 33G 4.7G 26G 16% /
  /dev/sda1 20M 5.8M 13M 31% /boot
  none 503M 0 503M 0% /dev/shm

<a name="rack1up">[imajes@rack1 imajes]$ uptime</a>
 
  9:10pm up 121 days, 5:06, 1 user, load average: 0.24, 0.10, 0.03
</pre>

<p>originally compiled by james cox, 2003</p>
<p>last update: $Id$</p>
<?php
foot();

