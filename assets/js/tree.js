class Tree {
    root;

    toString() {
        return this.root.toString()
    }
}

class Node {
    leftNode;
    rightNode;
    parentNode;
    val;
    color;

    get previous() {
        if (this.left != null) {
            let node = this.left

            while (node.right != null) {
                node = node.right
            }

            return node
        }

        if (this.parent != null) {
            if (this.parent.right === this) {
                return this.parent
            }

            if (this.parent.left === this) {
                let node = this
                while (node.parent.right !== node) {
                    if (node.parent.parent == null) {
                        return null;
                    }

                    node = node.parent
                }

                return node.parent
            }

        }

        return null
    }

    get next() {
        if (this.right != null) {
            let node = this.right

            while (node.left != null) {
                node = node.left
            }

            return node
        }

        if (this.parent != null) {
            if (this.parent.left === this) {
                return this.parent
            }

            if (this.parent.right === this) {
                let node = this
                while (node.parent.left !== node) {
                    if (node.parent.parent == null) {
                        return null;
                    }

                    node = node.parent
                }

                return node.parent
            }

        }

        return null
    }

    get left() {
        return this.leftNode
    }

    set left(node) {
        this.leftNode = node
    }

    get right() {
        return this.rightNode
    }

    set right(node) {
        this.rightNode = node
    }

    get parent() {
        return this.parentNode
    }

    set parent(node) {
        this.parentNode = node
    }

    get value() {
        return this.val
    }

    set value(value) {
        this.val = value;
    }

    get color() {
        return this.color
    }

    set color(color) {
        this.color = color
    }

    toString() {
        return `${this.left.toString()}${this.value.toString()}, ${this.right.toString()}`
    }
}