//----------------------------------------------------------------------------------------

function isArray(obj){
    return !!obj && Array === obj.constructor;
} 

//----------------------------------------------------------------------------------------

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

