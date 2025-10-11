from flask import Flask, request, jsonify
from mlxtend.frequent_patterns import apriori, association_rules
import pandas as pd

app = Flask(__name__)

@app.route('/apriori', methods=['POST'])
def run_apriori():
    data = request.get_json() or {}
    transactions = data.get('transactions', [])
    min_support = float(data.get('min_support', 0.5))
    min_confidence = float(data.get('min_confidence', 0.7))

    try:
        if not transactions:
            return jsonify({"itemsets": [], "rules": []})

        df = pd.DataFrame.from_records(transactions)
        if 'items' not in df.columns:
            return jsonify({"error": "payload must contain 'items' per transaction"}), 400

        all_items = sorted({item for sublist in df['items'] for item in sublist})
        encoded = pd.DataFrame([{item: (item in row) for item in all_items} for row in df['items']])

        # Batasi sampai 3-itemset
        freq = apriori(
            encoded,
            min_support=min_support,
            use_colnames=True
        )

        # Jika tidak ada frequent itemset, langsung kembalikan kosong
        if freq.empty:
            return jsonify({"itemsets": [], "rules": []})

        num_tx = encoded.shape[0]
        itemsets = []
        for _, r in freq.iterrows():
            items = list(r['itemsets']) if hasattr(r['itemsets'], '__iter__') else r['itemsets']
            itemsets.append({
                'itemset_number': len(items),
                'itemsets': items,
                'support': float(round(r['support'], 4)),
                'frequency': int(round(r['support'] * num_tx))
            })

        rules_raw = association_rules(freq, metric="confidence", min_threshold=min_confidence)
        rules = []
        for _, r in rules_raw.iterrows():
            a = list(r['antecedents'])
            c = list(r['consequents'])
            rules.append({
                'antecedents': a,
                'consequents': c,
                'itemset_number': len(a) + len(c), 
                'support': float(round(r['support'], 4)),
                'confidence': float(round(r['confidence'], 4)),
                'lift': float(round(r['lift'], 4)),
                'support_antecedents': float(round(r['antecedent support'], 4)), 
                'support_consequents': float(round(r['consequent support'], 4))
            })

        return jsonify({"itemsets": itemsets, "rules": rules})

    except Exception as e:
        import traceback; traceback.print_exc()
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(port=5000)
