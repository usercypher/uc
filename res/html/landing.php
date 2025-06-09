<?php 

$app = $data['app'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore ongoing personal projects by Usercypher, showcasing development, technology, software, and more. Stay updated with project progress, insights, and beta releases.">
    <meta name="author" content="usercypher">
    <meta name="keywords" content="Usercypher, personal projects, development, technology, software, ongoing development, prototypes, beta projects">
    <meta name="robots" content="index, follow">
    <title>Usercypher | Personal Projects in Development</title>

    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            color: #333;
            margin: 0;
            padding: 0;
            text-align: justify;
        }

        header {
            background: linear-gradient(90deg, #007BFF, #0056b3);
            color: white;
            text-align: center;
            padding: 80px 20px;
        }

        header h1 {
            font-size: 3rem;
            margin: 10px 0;
        }

        header p {
            font-size: 1.25rem;
            margin: 10px auto;
            max-width: 600px;
        }

        .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            background-color: #FFFFFF;
            color: #007BFF;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .btn-primary:hover {
            background-color: #DDDDDD;
        }

        .content {
            margin: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0px 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            border-left: 4px solid #007BFF;
        }

        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }

        p {
            line-height: 1.6;
        }

        a {
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #f8f8f8;
            color: #555;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Welcome to Usercypher</h1>
        <p>Explore my personal projects in development. Stay updated with my progress, insights, and upcoming releases as I continue to innovate and grow as a developer.</p>
        <a href="#projects" class="btn-primary">Explore My Projects</a>
    </header>

    <div class="content">
        <h2>About Usercypher</h2>
        <p>Welcome to my personal portfolio! Here, I share the projects I'm currently working on, from software development to experimental technology. These projects reflect my growing skills and passion for innovation. Join me on this exciting journey!</p>
    </div>

    <div id="projects" class="content">
        <h2>Projects</h2>
        <ul>
            <li><a href="<?php echo $app->url('route', 'home'); ?>">Homepage <span style="color: red; font-weight: bold;">(DEVELOPMENT)</span></a></li>
        </ul>
    </div>

    <div class="content">
        <h2>Useful Links</h2>
        <ul>
            <li><a href="https://www.webhostmost.com/">Hosting Provider: WebHostMost</a></li>
            <li><a href="https://github.com/usercypher/">GitHub Repository - Check out my code!</a></li>
            <li><a href="mailto:lmb.usercypher@gmail.com">Contact Me</a></li>
        </ul>
    </div>

    <div class="content">
        <h2>Privacy Policy</h2>
        <p><strong>Data Collection:</strong> We collect necessary data, such as your name and email address, to provide and improve our services. This data is required for the website to function properly.</p>
        <p><strong>Data Usage:</strong> The collected data will only be used for the purpose of providing the services offered on this website. We do not share or sell your data to third parties.</p>
        <p><strong>Cookies:</strong> This website uses cookies to enhance your experience. By using the website, you consent to the use of cookies as described in this policy.</p>
        <p><strong>Data Retention:</strong> Your data is stored by our hosting provider only for as long as necessary for the functioning of the website. You may request the removal of your data at any time.</p>
        <p><strong>Changes:</strong> This Privacy Policy may be updated periodically. Any changes will be posted here, and we encourage you to review it regularly.</p>
    </div>

    <div class="content">
        <h2>Terms of Service</h2>
        <p><strong>Acceptance:</strong> By using this website, you agree to comply with these Terms of Service. If you do not agree, please do not use this website.</p>
        <p><strong>Intellectual Property:</strong> All content on this website is owned by the website owner and is protected by intellectual property laws.</p>
        <p><strong>Limitation of Liability:</strong> This website is provided "as is," and we are not responsible for any errors or damages arising from the use of the site.</p>
        <p><strong>Modifications:</strong> We may update these terms at any time. Any changes will be posted here, and continued use of the website will indicate your acceptance of those changes.</p>
    </div>

    <div class="content">
        <h2>Disclaimer</h2>
        <p><strong>General:</strong> The content on this website is for informational purposes only. We make no guarantees regarding the accuracy or completeness of the information.</p>
        <p><strong>Professional Advice:</strong> This website does not provide professional advice, and the content should not be considered a substitute for professional legal, financial, or medical advice.</p>
        <p><strong>External Links:</strong> This website may contain links to external sites. We are not responsible for the content or accuracy of those sites.</p>
        <p><strong>Liability:</strong> We are not liable for any damages arising from the use of this website, including any loss of data or service interruptions.</p>
        <p><strong>Prototype Notice:</strong> The projects are prototypes intended to demonstrate concepts, features, or processes. While the interactions, such as making transactions or submitting information, are part of the prototype's functionality, they do not result in real-world actions like payments, deliveries, or services. Please note that some actions may involve the storage of data in a database for demonstration purposes, but no actual changes will occur beyond the scope of the prototype. The purpose of these projects is purely for demonstration.</p>
    </div>

    <footer>
        <p>If you have questions or feedback, feel free to <a href="mailto:lmb.usercypher@gmail.com">contact me</a>.</p>
    </footer>
</body>
</html>
