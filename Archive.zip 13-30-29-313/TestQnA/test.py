import re

# File paths
input_file = 'Meghalaya-GK.txt'
output_file = 'Meghalaya-GK-fixed.txt'

with open(input_file, 'r', encoding='utf-8') as f:
    content = f.read()

# This pattern assumes each block ends with "Answer: <option>"
question_blocks = re.findall(
    r'(.*?\n\s*a\)\s.*?\n\s*b\)\s.*?\n\s*c\)\s.*?\n\s*d\)\s.*?\n\s*Answer:\s*[a-dA-D])',
    content,
    re.DOTALL
)

# Renumber and format
output_lines = []
for i, block in enumerate(question_blocks, start=1):
    # Remove any previous numbering like "123. "
    block = re.sub(r'^\s*\d+\.\s*', '', block.strip())
    output_lines.append(f"{i}. {block.strip()}")

# Write to new file
with open(output_file, 'w', encoding='utf-8') as f:
    f.write('\n\n'.join(output_lines))

print(f"[✅] Renumbered {len(output_lines)} questions. Output saved to '{output_file}'")
