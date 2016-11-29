FROM ubuntu:latest

RUN apt-get update

RUN apt-get install -y sudo
RUN apt-get install -y curl

RUN apt-get install -y libstdc++6
RUN apt-get install -y gcc
RUN apt-get install -y g++

RUN apt-get install -y python3 python3-pip python3-dev python3-numpy

RUN curl -sSf https://static.rust-lang.org/rustup.sh | sh

RUN apt-get install -y python-software-properties

RUN apt-get install -y software-properties-common
RUN apt-get install -y openjdk-8-jdk

RUN apt-get install -y scala

RUN apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys 3FA7E0328081BFF6A14DA29AA6A19B38D3D831EF
RUN echo "deb http://download.mono-project.com/repo/debian wheezy main" | sudo tee /etc/apt/sources.list.d/mono-xamarin.list
RUN apt-get update
RUN apt-get install -y mono-devel

RUN apt-get install -y ruby
RUN gem install bundler 

RUN apt-get install -y golang

RUN apt-get install -y php

RUN apt-get install -y build-essential
RUN curl -sL https://deb.nodesource.com/setup_7.x | bash -
RUN apt-get install -y nodejs

RUN apt-get install -y ocaml

# RUN echo 'deb http://ppa.launchpad.net/hvr/ghc/ubuntu trusty main' > /etc/apt/sources.list.d/ghc.list && \
#     echo 'deb http://download.fpcomplete.com/debian/jessie stable main'| tee /etc/apt/sources.list.d/fpco.list && \
#     # hvr keys
#     apt-key adv --keyserver keyserver.ubuntu.com --recv-keys F6F88286 && \
#     # fpco keys
#     apt-key adv --keyserver keyserver.ubuntu.com --recv-keys C5705533DA4F78D8664B5DC0575159689BEFB442 && \
#     apt-get update && \
#     apt-get install -y --no-install-recommends cabal-install-1.24 ghc-8.0.1 happy-1.19.5 alex-3.1.7 \
#             stack zlib1g-dev libtinfo-dev libsqlite3-0 libsqlite3-dev ca-certificates g++ git && \
#     rm -rf /var/lib/apt/lists/*
#
# ENV PATH /root/.cabal/bin:/root/.local/bin:/opt/cabal/1.24/bin:/opt/ghc/8.0.1/bin:/opt/happy/1.19.5/bin:/opt/alex/3.1.7/bin:$PATH

# Haskell stack installed by following instructions from 
# https://docs.haskellstack.org/en/stable/install_and_upgrade/#ubuntu

# 1.Get the FP Complete key:
RUN sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys 575159689BEFB442

# 2.APT sources for Ubuntu 16.04 (version number came from running `lsb_release -a` inside docker image)
RUN echo 'deb http://download.fpcomplete.com/ubuntu xenial main'|sudo tee /etc/apt/sources.list.d/fpco.list

# 3.Update and install
RUN sudo apt-get update && sudo apt-get install stack -y
