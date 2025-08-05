from flask import Flask, request, jsonify
from mlxtend.frequent_patterns import apriori, association_rules
import pandas as pd

app = Flask(__name__)

@app.route('/apriori', methods=['POST'])
def run_apriori():
    data = request.get_json()
    transactions = data.get('transactions', [])
    min_support = data.get('min_support', 0.5)
    min_confidence = data.get('min_confidence', 0.7)

    try:
        df = pd.DataFrame.from_records(transactions)
        all_items = sorted({item for sublist in df['items'] for item in sublist})
        encoded = pd.DataFrame([{item: (item in row) for item in all_items} for row in df['items']])

        freq = apriori(encoded, min_support=min_support, use_colnames=True)

        # Fix: Konversi frozenset pada itemsets
        itemsets = []
        for _, row in freq.iterrows():
            itemsets.append({
                'support': float(round(row['support'], 4)),
                'itemsets': list(row['itemsets']) if isinstance(row['itemsets'], frozenset) else row['itemsets']
            })

        # Fix: Konversi frozenset pada rules
        rules_raw = association_rules(freq, metric="confidence", min_threshold=min_confidence)
        rules = []
        for _, row in rules_raw.iterrows():
            rules.append({
                'antecedents': list(row['antecedents']),
                'consequents': list(row['consequents']),
                'support': float(round(row['support'], 4)),
                'confidence': float(round(row['confidence'], 4)),
                'lift': float(round(row['lift'], 4))
            })

        return jsonify({
            "itemsets": itemsets,
            "rules": rules
        })

    except Exception as e:
        import traceback
        traceback.print_exc()
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    app.run(port=5000)
