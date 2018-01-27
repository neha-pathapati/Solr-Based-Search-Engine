import networkx as nx

print 'Creating NetworkX graph from edge list and calculating page rank'

edgeList = open('/Users/nehapathapati/Desktop/CSCI 572 - IR/Homework 4/edgeList.txt', 'rb')
G = nx.read_edgelist(edgeList, create_using = nx.DiGraph())

# Find page rank
pr = nx.pagerank(G, alpha = 0.85, personalization = None, max_iter = 30, tol = 1e-06, nstart = None, weight = 'weight', dangling = None)

# Write to Page Rank file
f = open('/Users/nehapathapati/Desktop/CSCI 572 - IR/Homework 4/external_pageRankFile.txt', 'w')

dir = '/Users/nehapathapati/Desktop/CSCI 572 - IR/Homework 4/solr-7.1.0/server/solr/irhw4/BG/BG/'
for key, val in pr.items():
    f.write(str(dir+key) + "=" + str(val) + "\n")

print 'Done!'
