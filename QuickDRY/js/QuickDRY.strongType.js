class strongType{

    fromData(data) {
        const existingProps = new Set(Object.getOwnPropertyNames(this));
        const missing = [];

        for (let key in data) {
            if (!existingProps.has(key)) {
                missing.push(`${key} = null;`);
            }
            this[key] = data[key];
        }

        if (missing.length) {
            console.log(`Missing properties in ${this.constructor.name}:\n` + missing.join("\n"));
        }
    }
}