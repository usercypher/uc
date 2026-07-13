package fcgiclient

import (
	"io"
	"net"
	"time"
)

const (
	FCGI_PARAMS      uint8 = 4
	FCGI_STDIN       uint8 = 5
	FCGI_STDOUT      uint8 = 6
	FCGI_END_REQUEST uint8 = 3

	headerSize = 8
	maxContent = 65535
	maxBuffer  = headerSize + maxContent + 255
)

type FCGIClient struct {
	conn      net.Conn
	readBuf   []byte
	hdr       [headerSize]byte
	buf       [maxBuffer]byte
	Keepalive uint8
}

func Dial(network, address string) (*FCGIClient, error) {
	conn, err := net.Dial(network, address)
	if err != nil {
		return nil, err
	}
	return &FCGIClient{conn: conn}, nil
}

func DialTimeout(network, address string, timeout time.Duration) (*FCGIClient, error) {
	conn, err := net.DialTimeout(network, address, timeout)
	if err != nil {
		return nil, err
	}
	return &FCGIClient{conn: conn}, nil
}

func (c *FCGIClient) Close() error {
	return c.conn.Close()
}

func (c *FCGIClient) Do(p map[string]string, req io.Reader) (io.Reader, error) {
	beginRecord := [16]byte{1, 1, 0, 1, 0, 8, 0, 0, 0, 1, c.Keepalive, 0, 0, 0, 0, 0}
	if _, err := c.conn.Write(beginRecord[:]); err != nil {
		return nil, err
	}

	pos := headerSize
	for k, v := range p {
		kl, vl := len(k), len(v)
		if pos+8+kl+vl > headerSize+maxContent {
			contentLength := pos - headerSize
			paddingLength := int(-contentLength & 7)
			c.buf[0], c.buf[1], c.buf[2], c.buf[3] = 1, FCGI_PARAMS, 0, 1
			c.buf[4] = byte(contentLength >> 8)
			c.buf[5] = byte(contentLength)
			c.buf[6] = byte(paddingLength)
			c.buf[7] = 0

			if _, err := c.conn.Write(c.buf[:pos+paddingLength]); err != nil {
				return nil, err
			}
			pos = headerSize
		}

		if kl > 127 {
			c.buf[pos] = byte(kl>>24) | 0x80
			c.buf[pos+1] = byte(kl >> 16)
			c.buf[pos+2] = byte(kl >> 8)
			c.buf[pos+3] = byte(kl)
			pos += 4
		} else {
			c.buf[pos] = byte(kl)
			pos++
		}

		if vl > 127 {
			c.buf[pos] = byte(vl>>24) | 0x80
			c.buf[pos+1] = byte(vl >> 16)
			c.buf[pos+2] = byte(vl >> 8)
			c.buf[pos+3] = byte(vl)
			pos += 4
		} else {
			c.buf[pos] = byte(vl)
			pos++
		}
		pos += copy(c.buf[pos:], k)
		pos += copy(c.buf[pos:], v)
	}

	if pos > headerSize {
		contentLength := pos - headerSize
		paddingLength := int(-contentLength & 7)
		c.buf[0], c.buf[1], c.buf[2], c.buf[3] = 1, FCGI_PARAMS, 0, 1
		c.buf[4] = byte(contentLength >> 8)
		c.buf[5] = byte(contentLength)
		c.buf[6] = byte(paddingLength)
		c.buf[7] = 0
		if _, err := c.conn.Write(c.buf[:pos+paddingLength]); err != nil {
			return nil, err
		}
	}
	c.buf[0], c.buf[1], c.buf[2], c.buf[3], c.buf[4], c.buf[5], c.buf[6], c.buf[7] = 1, FCGI_PARAMS, 0, 1, 0, 0, 0, 0
	if _, err := c.conn.Write(c.buf[:headerSize]); err != nil {
		return nil, err
	}

	if req != nil {
		for {
			n, err := req.Read(c.buf[headerSize : headerSize+maxContent])
			if n > 0 {
				paddingLength := int(-n & 7)
				c.buf[0], c.buf[1], c.buf[2], c.buf[3] = 1, FCGI_STDIN, 0, 1
				c.buf[4] = byte(n >> 8)
				c.buf[5] = byte(n)
				c.buf[6] = byte(paddingLength)
				c.buf[7] = 0
				if _, err := c.conn.Write(c.buf[:headerSize+n+paddingLength]); err != nil {
					return nil, err
				}
			}
			if err == io.EOF {
				break
			}
			if err != nil {
				return nil, err
			}
		}
	}
	c.buf[0], c.buf[1], c.buf[2], c.buf[3], c.buf[4], c.buf[5], c.buf[6], c.buf[7] = 1, FCGI_STDIN, 0, 1, 0, 0, 0, 0
	if _, err := c.conn.Write(c.buf[:headerSize]); err != nil {
		return nil, err
	}

	return c, nil
}

func (c *FCGIClient) Read(p []byte) (int, error) {
	for len(c.readBuf) == 0 {
		if _, err := io.ReadFull(c.conn, c.hdr[:]); err != nil {
			return 0, err
		}
		recType := c.hdr[1]
		contentLength := (int(c.hdr[4]) << 8) | int(c.hdr[5])
		paddingLength := int(c.hdr[6])

		if recType == FCGI_END_REQUEST {
			return 0, io.EOF
		}

		total := contentLength + paddingLength
		if total > 0 {
			if _, err := io.ReadFull(c.conn, c.buf[:total]); err != nil {
				return 0, err
			}
		}

		if recType == FCGI_STDOUT {
			c.readBuf = c.buf[:contentLength]
			break
		}
	}

	n := copy(p, c.readBuf)
	c.readBuf = c.readBuf[n:]
	return n, nil
}
