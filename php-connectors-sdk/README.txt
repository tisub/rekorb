INSTALLATION :

- Apache 2.2+
- PHP 5.3+
- Copy all files to your www root directory (vhosts supported)


INSTRUCTIONS :

1) Place your connector files (.php + .json) in the CONNECTORS folder

2) Launch the website
	2.1) Choose the connector source file
	2.2) Setup the config parameters
	2.3) Setup the input and output interfaces
	2.4) Type in a sample message
	2.5) Choose which interfaces should be activated

3) Review the log messages

NOTES : 
	- There is no security enforced on this local tool
	- You should not use anything from the com\busit\local namespace because those are 
	  part of this local environment and will not be available on the Busit platform.
	- Your connector should be contained in ONE single file and everything inside classes
	- See http://busit.github.io