# Sunset Content

This folder is for holding HTML and CSS relating to the Sunsetting of the website.

## Setup

To get set up, install Pandoc:

``` bash
brew install pandoc
```

## Development and Deployment

``` bash
# To convert the Markdown to HTML:
pandoc -s index.md -c style.css -o index.html

# To deploy to S3
aws s3 sync . s3://septastats.com/

```
