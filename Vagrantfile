# -*- mode: ruby -*-
# vi: set ft=ruby :

#
# Sample command line for bringing up an instance and configuring it:
#
# vagrant destroy -f main && vagrant up main && ./go.sh -i ./inventory/vagrant -l vagrant-btsync-main
#

Vagrant.configure("2") do |config|

	#
	# Cache anything we download with apt-get
	#
	if Vagrant.has_plugin?("vagrant-cachier")
		config.cache.scope = :box
	end

	#
	# This is our main host.  It also runs a Splunk indexer and search head.
	#
	config.vm.define :main do |host|

		#
		# Ubuntu 14.04 LTS
		#
		host.vm.box = "ubuntu/trusty64"


		host.vm.hostname = "main"
		host.vm.network "private_network", ip: "10.0.10.101"

		#
		# Change ownership of files to the user that PHP FCGI runs as.
		#
		config.vm.synced_folder ".", "/vagrant", owner: "www-data"

		#
		# Use the default insecure SSH key from Vagrant.
		# This way, we can connect via Ansible
		#
		config.ssh.insert_key = false

		#
		# HTTP
		#
		host.vm.network :forwarded_port, guest: 80, host: 8080

		#
		# Set the amount of RAM and CPU cores
		#
		host.vm.provider "virtualbox" do |v|
			#
			# I need to have at least a Gig of RAM or else we'll get "std:bad_alloc" 
			# errors.  I should be able to (eventually) fix this by having a swapfile.
			#
			v.memory = 1024
			v.cpus = 2
		end

		#
		# Updating the plugins at start time never ends well.
		#
		if Vagrant.has_plugin?("vagrant-vbguest")
			config.vbguest.auto_update = false
		end

		host.vm.provision "shell", path: "bin/provision-vagrant.sh"

	end

end


