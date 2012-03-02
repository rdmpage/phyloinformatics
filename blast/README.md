# BLAST sequence get a tree

#### Overview
This is a simple tool to take a DNA sequence accession number and create a tree from the _n_ nearest sequences as determined by NCBI's BLAST service. 

#### Requirements
To run this service you must have [PAUP* 4.0](http://paup.csit.fsu.edu/) and [ClustalW2](http://www.clustal.org/clustal2/) installed. The tmp folder needs to be writable by your web server. You will also need the services and treeviewer modules in this repository.

#### How it works
The service submits the BLAST job then polls the NCBI BLAST server waiting for the results. Once they are ready it retrieves them, creates a FASTA file, calls clustalw2 to align them, then builds a tree using PAUP*. The tree is displayed in SVG
