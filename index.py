#!/usr/bin/env python
import argparse
from flup.server.fcgi import WSGIServer

def application(environ, start_response):
    status = '200 OK'
    response_headers = [('Content-type', 'text/plain')]
    start_response(status, response_headers)
    return [b'Hello, World!']

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Python FastCGI Server')
    parser.add_argument('-b', '--bind', 
                       default='/tmp/python.sock',
                       help='Socket path to bind to (default: /tmp/python.sock)')

    args = parser.parse_args()

    print(f'Starting FastCGI server on {args.bind}')
    WSGIServer(application, bindAddress=args.bind).run()
