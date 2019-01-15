//----------------------------------------------------------------------------------------

function isArray(obj){
    return !!obj && Array === obj.constructor;
} 

// http://stackoverflow.com/a/29585704
function cartesianProduct(a) { // a = array of array
    var i, j, l, m, a1, o = [];
    if (!a || a.length == 0) return a;

    a1 = a.splice(0, 1)[0]; // the first array of a
    a = cartesianProduct(a);
    for (i = 0, l = a1.length; i < l; i++) {
        if (a && a.length) for (j = 0, m = a.length; j < m; j++)
            o.push([a1[i]].concat(a[j]));
        else
            o.push([a1[i]]);
    }
    return o;
}

function numberCompare(a, b) {
  return a - b;
}

//----------------------------------------------------------------------------------------
function allocateSquareMatrix(n, value=null) {
    let a = new Array(n);
    for (let i = 0; i < n; i++) {
        a[i] = new Array(n);
        if(value !== null) a[i].fill(value);
    }
    return a;
}

function arrayCopy(a) {
    let b = new Array(a.length),
        i = a.length;
    while(i--) { b[i] = a[i]; }
    return b;
}

function sumRows(a) {
    let sum,
        n = a.length,
        sums = new Array(n);

    for (let i = 0; i < n; i++) {
        sum = 0;
        for (let j = 0; j < n; j++) {
            if (a[i][j] === undefined) continue;
            sum += a[i][j];
        }
        sums[i] = sum;
    }

    return sums;
}

function sortWithIndices(toSort, skip=-1, timsort=false) {
    var n = toSort.length;
    var indexCopy = new Array(n);
    var valueCopy = new Array(n);
    var i2 = 0;

    for (var i = 0; i < n; i++) {
        if (toSort[i] === -1 || i === skip) continue;
        indexCopy[i2] = i;
        valueCopy[i2++] = toSort[i];
    }
    indexCopy.length = i2;
    valueCopy.length = i2;
    
/*
    if (timsort) {
        TimSort.sort(indexCopy, (a, b) => toSort[a] - toSort[b]);
    }
    else {
        indexCopy.sort((a, b) => toSort[a] - toSort[b]);
    }

    TimSort.sort(indexCopy,function(left, right) {
        return toSort[left] - toSort[right];
    });
*/    

	indexCopy.sort(numberCompare);
	indexCopy.sort(function(left, right) {
        return toSort[left] - toSort[right];
    });

	/*
    sort(indexCopy,function(left, right) {
        return toSort[left] - toSort[right];
    });
    */

    valueCopy.sortIndices = indexCopy;
    for (var j = 0; j < i2; j++) {
        valueCopy[j] = toSort[indexCopy[j]];
    }
    return valueCopy;
}

//----------------------------------------------------------------------------------------
class RapidNeighborJoining {
    constructor(D, taxa, copyDistanceMatrix=false, taxonIdAccessor=(d)=>d.name) {
        if (taxa.length != D.length) {
            console.error("Row/column size of the distance matrix does not agree with the size of taxa matrix");
            return;
        }
        let N = this.N = taxa.length;
        this.cN = this.N;
        if (copyDistanceMatrix) {
            this.D = new Array(N);
            for (let i = 0; i < N; i++) {
                this.D[i] = arrayCopy(D[i]);
            }
        }
        else {
            this.D = D;
        }
        this.taxa = taxa;
        this.labelToTaxon = {};
        this.currIndexToLabel = new Array(N);
        this.rowChange = new Array(N);
        this.newRow = new Array(N);
        this.labelToNode = new Array(2 * N);
        this.nextIndex = N;
        this.initializeSI();
        this.removedIndices = new Set();
        this.indicesLeft = new Set();
        for (let i = 0; i < N; i++) {
            this.currIndexToLabel[i] = i;
            this.indicesLeft.add(i);
        }
        this.rowSumMax = 0;
        this.PNewick = "";
        this.taxonIdAccessor = taxonIdAccessor;
        return this;
    }

    initializeSI() {
        let N = this.N;

        this.I = new Array(N);
        this.S = new Array(N);

        for (let i = 0; i < N; i++) {
            let sortedRow = sortWithIndices(this.D[i], i, true);
            this.S[i] = sortedRow;
            this.I[i] = sortedRow.sortIndices;
        }
    }

    search() {

        let qMin = Infinity,
            D = this.D,
            cN = this.cN,
            n2 = cN - 2,
            S = this.S,
            I = this.I,
            rowSums = this.rowSums,
            removedColumns = this.removedIndices,
            uMax = this.rowSumMax,
            q, minI = -1, minJ = -1, c2;

        // initial guess for qMin
        for (let r = 0; r < this.N; r++) {
            if (removedColumns.has(r)) continue;
            c2 = I[r][0];
            if (removedColumns.has(c2)) continue;
            q = D[r][c2] * n2 - rowSums[r] - rowSums[c2];
            if (q < qMin) {
                qMin = q;
                minI = r;
                minJ = c2;
            }
        }

        for (let r = 0; r < this.N; r++) {
            if (removedColumns.has(r)) continue;
            for (let c = 0; c < S[r].length; c++) {
                c2 = I[r][c];
                if (removedColumns.has(c2)) continue;
                if (S[r][c] * n2 - rowSums[r] - uMax > qMin) break;
                q = D[r][c2] * n2 - rowSums[r] - rowSums[c2];
                if (q < qMin) {
                    qMin = q;
                    minI = r;
                    minJ = c2;
                }
            }
        }

        return {minI, minJ};
    }

    run() {
        let minI, minJ,
            d1, d2,
            l1, l2,
            node1, node2, node3,
            self = this;

        function setUpNode(label, distance) {
            let node;
            if(label < self.N) {
                node = new PhyloNode(self.taxa[label], distance);
                self.labelToNode[label] = node;
            }
            else {
                node = self.labelToNode[label];
                node.setLength(distance);
            }
            return node;
        }
        
        //alert(JSON.stringify(this.D));

        this.rowSums = sumRows(this.D);
        for (let i = 0; i < this.cN; i++) {
            if (this.rowSums[i] > this.rowSumMax) this.rowSumMax = this.rowSums[i];
        }

        while(this.cN > 2) {
            //if (this.cN % 100 == 0 ) console.log(this.cN);
            ({ minI, minJ } = this.search());

            d1 = 0.5 * this.D[minI][minJ] + (this.rowSums[minI] - this.rowSums[minJ]) / (2 * this.cN - 4);
            d2 = this.D[minI][minJ] - d1;

            l1 = this.currIndexToLabel[minI];
            l2 = this.currIndexToLabel[minJ];

            node1 = setUpNode(l1, d1);
            node2 = setUpNode(l2, d2);
            node3 = new PhyloNode(null, null, node1, node2);

            this.recalculateDistanceMatrix(minI, minJ);
            let sorted = sortWithIndices(this.D[minJ], minJ, true);
            this.S[minJ] = sorted;
            this.I[minJ] = sorted.sortIndices;
            this.S[minI] = this.I[minI] = [];
            this.cN--;

            this.labelToNode[this.nextIndex] = node3;
            this.currIndexToLabel[minI] = -1;
            this.currIndexToLabel[minJ] = this.nextIndex++;
        }

        let left = this.indicesLeft.values();
        minI = left.next().value;
        minJ = left.next().value;

        l1 = this.currIndexToLabel[minI];
        l2 = this.currIndexToLabel[minJ];
        d1 = d2 = this.D[minI][minJ] / 2;

        node1 = setUpNode(l1, d1);
        node2 = setUpNode(l2, d2);

        this.P = new PhyloNode(null, null, node1, node2);
    }

    recalculateDistanceMatrix(joinedIndex1, joinedIndex2) {
        let D = this.D,
            n = D.length,
            sum = 0, aux, aux2,
            removedIndices = this.removedIndices,
            rowSums = this.rowSums,
            newRow = this.newRow,
            rowChange = this.rowChange,
            newMax = 0;

        removedIndices.add(joinedIndex1);
        for (let i = 0; i < n; i++) {
            if (removedIndices.has(i)) continue;
            aux = D[joinedIndex1][i] + D[joinedIndex2][i];
            aux2 = D[joinedIndex1][joinedIndex2];
            newRow[i] = 0.5 * (aux - aux2);
            sum += newRow[i];
            rowChange[i] = -0.5 * (aux + aux2);
        }
        for (let i = 0; i < n; i++) {
            D[joinedIndex1][i] = -1;
            D[i][joinedIndex1] = -1;
            if (removedIndices.has(i)) continue;
            D[joinedIndex2][i] = newRow[i];
            D[i][joinedIndex2] = newRow[i];
            rowSums[i] += rowChange[i];
            if (rowSums[i] > newMax) newMax = rowSums[i];
        }
        rowSums[joinedIndex1] = 0;
        rowSums[joinedIndex2] = sum;
        if (sum > newMax) newMax = sum;
        this.rowSumMax = newMax;
        this.indicesLeft.delete(joinedIndex1);
    }

    createNewickTree(node) {
        if (node.taxon) { // leaf node
            this.PNewick += this.taxonIdAccessor(node.taxon);
        }
        else { // node with children
            this.PNewick += "(";
            for (let i = 0; i < node.children.length; i++) {
                this.createNewickTree(node.children[i]);
                if (i < node.children.length - 1) this.PNewick += ",";
            }
            this.PNewick += ")";
        }
        if (node.length) {
            this.PNewick += `:${node.length}`;
        }
    }

    getAsObject() {
        return this.P;
    }

    getAsNewick() {
        this.PNewick = "";
        this.createNewickTree(this.P);
        this.PNewick += ";";
        return this.PNewick;
    }
}

//----------------------------------------------------------------------------------------
class PhyloNode {

    constructor(taxon=null, length=null, child1=null, child2=null) {
        this.taxon = taxon;
        this.length = length;
        this.children = [];
        if (child1 !== null) this.children.push(child1);
        if (child2 !== null) this.children.push(child2);
    }
    setLength(length) {
        this.length = length;
    }
}

//----------------------------------------------------------------------------------------
function showtree(element_id, newick)
{
    var t = new Tree();
    var element = document.getElementById(element_id);
	t.Parse(newick);

	if (t.error != 0)
	{
		//document.getElementById('message').innerHTML='Error parsing tree';
	}
	else
	{
		//document.getElementById('message').innerHTML='Parsed OK';
				
		t.ComputeWeights(t.root);
		
		var td = null;
		
		//var selectmenu = document.getElementById('style');
		//var drawing_type = (selectmenu.options[selectmenu.selectedIndex].value);
		
		var drawing_type = 'circlephylogram';
		
		switch (drawing_type)
		{
			case 'rectanglecladogram':
				td = new RectangleTreeDrawer();
				break;
		
			case 'phylogram':
				if (t.has_edge_lengths)
				{
					td = new PhylogramTreeDrawer();
				}
				else
				{
					td = new RectangleTreeDrawer();
				}
				break;
				
			case 'circle':
				td = new CircleTreeDrawer();
				break;
				
			case 'circlephylogram':
				if (t.has_edge_lengths)
				{
					td = new CirclePhylogramDrawer();
				}
				else
				{
					td = new CircleTreeDrawer();
				}
				break;
				
			case 'cladogram':
			default:
				td = new TreeDrawer();
				break;
		}
		
		// clear existing diagram, if any
		var svg = document.getElementById('svg');
		while (svg.hasChildNodes()) 
		{
			svg.removeChild(svg.lastChild);
		}
		
		
		var g = document.createElementNS('http://www.w3.org/2000/svg','g');
		g.setAttribute('id','viewport');
		svg.appendChild(g);
		
		
		td.Init(t, {svg_id: 'viewport', width:500, height:500, fontHeight:10, root_length:0.1} );
		
		td.CalcCoordinates();
		td.Draw();
		
		// font size
		var cssStyle = document.createElementNS('http://www.w3.org/2000/svg','style');
		cssStyle.setAttribute('type','text/css');
		
		var font_size = Math.floor(td.settings.height/t.num_leaves);
		font_size = Math.max(font_size, 1);
		
		var style=document.createTextNode("text{font-size:" + font_size + "px;}");
		cssStyle.appendChild(style);
		
		svg.appendChild(cssStyle);

		// label leaves...
		
		
		var n = new NodeIterator(t.root);
		var q = n.Begin();
		while (q != null)
		{
			if (q.IsLeaf())
			{
				switch (drawing_type)
				{
					case 'circle':
					case 'circlephylogram':
						var align = 'left';
						var angle = q.angle * 180.0/Math.PI;
						if ((q.angle > Math.PI/2.0) && (q.angle < 1.5 * Math.PI))
						{
							align = 'right';
							angle += 180.0;
						}
						drawRotatedText('viewport', q.xy, q.label, angle, align)
						break;
				
					case 'cladogram':
					case 'rectanglecladogram':
					case 'phylogram':
					default:				
						drawText('viewport', q.xy, q.label);
						break;
				}
			}
			q = n.Next();
		}
		
		
		//----
		var u = [];
		for (var i in label_to_bin) {
			if (u.indexOf(label_to_bin[i]) == -1)
			{
				u.push(label_to_bin[i]);
			}
		}
		
		
					// Colour scheme from d3js
					// https://github.com/mbostock/d3/wiki/Ordinal-Scales
					var category20  = ['#1f77b4','#aec7e8','#ff7f0e','#ffbb78','#2ca02c','#98df8a','#d62728','#ff9896','#9467bd','#c5b0d5','#8c564b','#c49c94','#e377c2','#f7b6d2','#7f7f7f','#c7c7c7','#bcbd22','#dbdb8d','#17becf','#9edae5'];
					var category20c = ['#3182bd','#6baed6','#9ecae1','#c6dbef','#e6550d','#fd8d3c','#fdae6b','#fdd0a2','#31a354','#74c476','#a1d99b','#c7e9c0','#756bb1','#9e9ac8','#bcbddc','#dadaeb','#636363','#969696','#bdbdbd','#d9d9d9'];
										
					// Get global transform matrix
					gCTM = g.getCTM();
					
					// color stuff
					$( "text" ).each(function( index ) {
											
						// idea from http://srufaculty.sru.edu/david.dailey/svg/getCTM.svg
						SVGRect = this.getBBox();
						
						var rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
							rect.setAttribute("x", SVGRect.x);
							rect.setAttribute("y", SVGRect.y);
							rect.setAttribute("width", SVGRect.width);
							rect.setAttribute("height", SVGRect.height);
							
							// Pick a colour
							var index = u.indexOf(label_to_bin[$(this).text()]) % 20;							
							//var index = 0;
							var colour = category20[index];
							
							rect.setAttribute("fill", colour);
							rect.setAttribute("opacity", 0.5);
							
							CTM=this.getCTM();
							s=CTM.a+" "+CTM.b+" "+CTM.c+" "+CTM.d+" "+CTM.e+" "+CTM.f;
							rect.setAttributeNS(null,"transform","translate("+ -gCTM.e + "," + -gCTM.f + "),matrix("+s+")");
							
							g.insertBefore(rect, this);						
					});		
		
		//----
		
		
				
		// Scale to fit window
		var bbox = svg.getBBox();
		
		var scale = Math.min(td.settings.width/bbox.width, td.settings.height/bbox.height);
		
		if ((drawing_type == 'circle') || (drawing_type == 'circlephylogram')) {
			//scale *= 0.2;
		} else {
			scale *= 0.8;
		}
		
		
		// move drawing to centre of viewport
		var viewport = document.getElementById('viewport');
		viewport.setAttribute('transform', 'scale(' + scale + ')');
		
		// centre
		bbox = svg.getBBox();
		if (bbox.x < 0)
		{
			viewport.setAttribute('transform', 'translate(' + -bbox.x + ' ' + -bbox.y + ') scale(' + scale + ')');
		}
		
		
		
		// pan
		//$('svg').svgPan('viewport');
	}
}
