import json
d = json.load(open('/tmp/tools.out'))
tools = d.get('result', {}).get('tools', [])
print('Count:', len(tools))
for t in tools:
    print(' -', t['name'])
