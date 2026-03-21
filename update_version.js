const fs = require('fs');

// 定义要更新的文件路径
const filePath = 'index.php';

function updateVersionNumber() {
    // 获取当前日期并格式化为 8 位数的版本号
    const now = new Date();
    const year = now.getFullYear().toString(); // 获取年份的最后两位
    const month = (now.getMonth() + 1).toString().padStart(2, '0'); // 获取月份并确保是两位数
    const day = now.getDate().toString().padStart(2, '0'); // 获取日期并确保是两位数
    const versionNumber = `${year}${month}${day}`;
    const newVersion = `Beat ${versionNumber}`;
    
    // 定义正则表达式来匹配版本号
    const pattern = /Beat \d{8}/g;
    let replacements = 0;

    try {
        // 使用 UTF-8 编码打开文件并读取内容
        const content = fs.readFileSync(filePath, 'utf8').split('\n');

        // 更新文件内容
        for (let i = 0; i < content.length; i++) {
            if (pattern.test(content[i])) {
                content[i] = content[i].replace(pattern, newVersion);
                replacements += 1;
                if (replacements >= 2) {  // 达到 2 次替换后停止
                    break;
                }
            }
        }

        // 如果有替换，写回文件
        if (replacements > 0) {
            fs.writeFileSync(filePath, content.join('\n'), 'utf8');
            console.log(`Updated ${replacements} version numbers to ${newVersion}.`);
        } else {
            console.log("No version number to update found.");
        }
    } catch (error) {
        console.error(`An error occurred: ${error.message}`);
    }
}

updateVersionNumber();
