# PHP + Flatbuffers tutorial

Work in progress

Test system: Fedora 30 + php 7.3.7.

# Folder structure

* fbs - source flatbuffers schemas
* src - test php files
* google/flatbuffers - source of the flatbuffers compiler from https://github.com/google/flatbuffers
* Schema - fbs files *compiled* to php

# Install
```
$ git clone git@github.com:dribtech/php-flatbuffers-tutorial.git
```

```
$ cd php-flatbuffers-tutorial
$ git submodule sync --recursive
$ git submodule update --init --recursive
```


# Build flatbuffers compiler

Google/flatbuffers are in the dir `google/flatbuffers`. 
To build flatbuffer compiler read https://google.github.io/flatbuffers/flatbuffers_guide_building.html

On my system (Fedora 30) I needed to do this: 
 
```
$ sudo dnf install clang
$ sudo dnf install cmake
```

```
$ cd google/flatbuffers
$ export CC=/usr/bin/clang
$ export CXX=/usr/bin/clang++ 
$ cmake -G "Unix Makefiles" -DCMAKE_BUILD_TYPE=Release
$ make
```

Now you have several `flat*` executables in the `google/flatbuffers`.

# Schema

Test schemas can be found in `./fbs`.

## `fbs/spaceship.fbs` 
It is Star Trek federation spaceship with few weapons, shield, 
and few more attributes such as position (3d vector), name and color.

## `fbs/spaceshiplist.fbs`
List of Star Trek federation spaceships.

Go back to project root and run:

```
$ ./google/flatbuffers/flatc --php fbs/spaceship.fbs
$ ./google/flatbuffers/flatc --php fbs/spaceshiplist.fbs 
```


## Compiled schemas

In `Schema`, there is schema *compiled* to php. Check it out.

Run:

```
$ composer install
```

# Test

`src/test-raw-flatbuffer.php` Creates, encodes and decodes 1000 federation
spaceships using flatbuffers.

`src/test-raw-json.php` Does the same but using build in json_* functions.


## Flatbuffers
```
$ time php src/test-raw-flatbuffer.php

bool(true)

real	0m0.137s
user	0m0.130s
sys	0m0.007s
```

## json_*
```
$ time php src/test-raw-json.php

bool(true)

real	0m0.022s
user	0m0.015s
sys	0m0.006s

```


## Result

json_* is 6x faster then flatbuffers.