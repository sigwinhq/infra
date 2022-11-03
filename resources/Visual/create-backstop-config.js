function createBackstopConfig(root = __dirname + "/public", scenarios = [], defaults = {}) {
    const { spawn } = require("child_process");
    const SERVER_PORT = 8080;
    const PREVIEW_BASE_URL = `http://localhost:${SERVER_PORT}/`;

    spawn(
        "node",
        [__dirname + "/http-server.js"],
        { detached: true, stdio: "inherit", env: { ...process.env, SERVER_PORT, ROOT: root } }
    ).unref();

    const config = {
        id: "puppeteer-chrome",
        viewports: [
            {
                label: "laptop",
                width: 1280,
                height: 800
            }
        ],
        scenarios: scenarios.map(function (file) {
            if (typeof file === "string") {
                const scenario = {...defaults, label: file, url: PREVIEW_BASE_URL + file};
                return scenario;
            } else if (typeof file === "object" && "path" in file) {
                if (typeof file.label !== "string") throw new Error(`${JSON.stringify(file)} - scenario must have a label property`);
                const scenario = {...defaults, ...file, label: `${file.path} ${file.label}`, url: PREVIEW_BASE_URL + file.path};
                return scenario;
            } else {
                throw new Error("Scenario must be a string or an object literal containing a path property");
            }
        }),
        scenarioLogsInReports: true,
        paths: {
            bitmaps_reference: "tests/visual/references",
            engine_scripts: __dirname,
            bitmaps_test: "var/backstop/tests",
            html_report: "var/backstop/reports",
            ci_report: "var/backstop/reports"
        },
        report: ["CI"],
        engine: "puppeteer",
        engineOptions: {
            args: ["--no-sandbox", "--allow-file-access-from-files"]
        },
        onReadyScript: "on-ready.js",
        asyncCaptureLimit: 5,
        asyncCompareLimit: 50,
        debug: false,
        debugWindow: false
    };

    return config;
}

module.exports = createBackstopConfig;
