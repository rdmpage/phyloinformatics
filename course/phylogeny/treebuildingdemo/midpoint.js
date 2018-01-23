// midpoint rooting

var counter = 0;


//----------------------------------------------------------------------------------------
Tree.prototype.FirstDescendant = function(p) {
	this.curnode = p.child;
	return this.curnode;
}

//----------------------------------------------------------------------------------------
Tree.prototype.NextDescendant = function() {
	this.curnode = this.curnode.sibling;
	return this.curnode;
}

//----------------------------------------------------------------------------------------
Tree.prototype.NextNonOGDescendant = function() {
	var q = this.NextDescendant();
	var done = false;
	while (!done) {
		if (!q) {
			done = true;
		}
		if (!done) {
			done = !q.marked;
		}
		if (!done) {
			q = this.NextDescendant();
		}
	}
	return q;
}

//----------------------------------------------------------------------------------------
Tree.prototype.MarkPath = function(p) {
	var q = p;
	while (q) {
		q.marked = true;
		q = q.ancestor;
	} 
}

//----------------------------------------------------------------------------------------
Tree.prototype.UnMarkPath = function(p) {
	var q = p;
	while (q) {
		q.marked = false;
		q = q.ancestor;
	} 
}

//----------------------------------------------------------------------------------------
Tree.prototype.ListOtherDesc = function(p) {
	var q = this.FirstDescendant(p);
	if (q.marked) {
		q = this.NextNonOGDescendant();
	}
	
	console.log("NextNonOGDescendant=" + q.label);
	
	/*
	if (this.add_there.IsLeaf() || this.add_there.child) {
		this.add_there.sibling = q;
		q.ancestor = this.add_there.ancestor;
	} else {
		this.add_there.child = q;
		q.ancestor = this.add_there;
	}
	*/
	
	if (p != this.root) {
		this.add_there.child = q;
		q.ancestor = this.add_there;
	} else {
		this.add_there.sibling = q;
		q.ancestor = this.add_there.ancestor;
	}
	
	this.add_there = q;
	console.log("q add_there=" + this.add_there.label);

	q = this.NextNonOGDescendant();
	while (q) {
	
		console.log("NextNonOGDescendant=" + q.label);
	
		this.add_there.sibling = q;
		q.ancestor = this.add_there.ancestor;
		this.add_there = q;
		q = this.NextNonOGDescendant();
	}
	this.add_there.sibling = null;
	
	console.log("ListOtherDesc add_there=" + this.add_there.label);
}

//----------------------------------------------------------------------------------------
Tree.prototype.ReRoot = function(outgroup, ingroup_edge, outgroup_edge) {
	if (!outgroup || (outgroup == this.root)) {
		return;
	}
	
	if (outgroup.ancestor == this.root) {
	
		// if tree had no branch lengths we'd ignore this case, but
		// for midpoint we may need to adjust edge lengths
		
		var ingroup = this.root.child;
		if (ingroup == outgroup) {
			ingroup = this.root.child.sibling;
		}
		
		ingroup.edge_length = ingroup_edge;
		outgroup.edge_length = outgroup_edge;
	
		return;
	}
	
	this.MarkPath(outgroup);

	var ingroup = new Node('ingroup');
	ingroup.ancestor = outgroup.ancestor;
	
	this.add_there = ingroup;
	var q = outgroup.ancestor;
	
	// Split outgroup edge length among out and ingroup (does this require things to be binary?)
	//var half = outgroup.edge_length/2.0;
	ingroup.edge_length = ingroup_edge;
	outgroup.edge_length = outgroup_edge;
	
	while (q) {
		console.log("ReRoot q=" + q.label);
	
		console.log('into ListOtherDesc');
		this.ListOtherDesc(q);
		console.log('outof ListOtherDesc');
		
		var previous_q = q;
		q = q.ancestor;
		
		if (q && (q != this.root)) {
			var p = new Node('x' + counter++);
			this.add_there.sibling = p;
			p.ancestor = this.add_there.ancestor;
			
			p.edge_length = previous_q.edge_length;
			p.label = previous_q.label;
			
			this.add_there = p;
		}
	}
	
	outgroup.ancestor.child = outgroup;
	outgroup.sibling = ingroup;
	this.root = outgroup.ancestor;
	
	// cleanup
	/*
	q = Root->GetAnc();
	while (q != NULL)
	{
		NodePtr p = q;
		q = q->GetAnc ();
		delete p;
	}
	*/
	
	this.root.ancestor = null;
	this.root.sibling = null;

	this.root.marked = false;
	outgroup.marked = false;

}

