const fs = require('fs');
const path = require('path');

function findBladeFiles(dir, fileList = []) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const filePath = path.join(dir, file);
        if (fs.statSync(filePath).isDirectory()) {
            findBladeFiles(filePath, fileList);
        } else if (filePath.endsWith('.blade.php')) {
            fileList.push(filePath);
        }
    }
    return fileList;
}

const targetDirs = [
    'resources/views/forms/components',
    'resources/views/schemas/components',
    'resources/views/actions'
];

let files = [];
targetDirs.forEach(dir => {
    if (fs.existsSync(dir)) {
        files = files.concat(findBladeFiles(dir));
    }
});

let modifiedCount = 0;

for (const filePath of files) {
    let content = fs.readFileSync(filePath, 'utf8');
    
    // Find @include('filament-flex-fields::partials.load-stylesheet', ['component' => '...'])
    const includeRegex = /^[ \t]*@include\('filament-flex-fields::partials\.load-stylesheet',\s*\['component'\s*=>\s*'([^']+)'\]\)\r?\n/m;
    
    const match = content.match(includeRegex);
    if (match) {
        const componentName = match[1];
        
        // Remove the include
        content = content.replace(includeRegex, '');
        
        // Now find x-load-src="..." and append x-load-css
        const xLoadSrcRegex = /(x-load-src="[^"]+")/m;
        if (content.match(xLoadSrcRegex)) {
            const xLoadCssStr = `x-load-css="@js(\\Bjanczak\\FilamentFlexFields\\Support\\FlexFieldAssets::stylesheetHrefsFor('${componentName}'))"`;
            content = content.replace(xLoadSrcRegex, `$1\n        ${xLoadCssStr}`);
            fs.writeFileSync(filePath, content, 'utf8');
            modifiedCount++;
            console.log(`Updated ${filePath}`);
        } else {
            console.log(`Skipped ${filePath}: x-load-src not found`);
        }
    }
}

console.log(`Modified ${modifiedCount} files.`);
