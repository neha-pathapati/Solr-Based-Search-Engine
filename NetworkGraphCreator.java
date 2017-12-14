import org.jsoup.*;
import org.jsoup.nodes.*;
import org.jsoup.select.*;
import java.io.*;
import java.util.*;

public class NetworkGraphCreator {

	public static void main(String[] args) throws IOException {
		// Create a hash map of HTML file names and their URLs
		String BG_csv = "/Users/nehapathapati/Desktop/CSCI 572 - IR/Homework 4/BG/Boston_GLobal_Map.csv";
		BufferedReader br = null;
        String line = "";
        HashMap<String,String> fileUrlMap = new HashMap<String,String>();
        HashMap<String,String> urlFileMap = new HashMap<String,String>();
  
        try {
            br = new BufferedReader(new FileReader(BG_csv));
            while ((line = br.readLine()) != null) 
            {
                String[] line_split = line.split(",");
                fileUrlMap.put(line_split[0], line_split[1]);
                urlFileMap.put(line_split[1], line_split[0]);
	        }
	    }
	    catch (Exception e) {
	         e.printStackTrace();
	    }
      
        // Create edge list
		File dir = new File("/Users/nehapathapati/Desktop/CSCI 572 - IR/Homework 4/BG/BG/");
		Set<String> edges = new HashSet<String>();
		
		for(File file : dir.listFiles()) {
			if(fileUrlMap.containsKey(file.getName())) {
				Document doc = Jsoup.parse(file, "UTF-8", fileUrlMap.get(file.getName()));
				Elements links = doc.select("a[href]");
				
				for(Element link : links)
	            {
	                String url = link.attr("abs:href").trim();
	                if(urlFileMap.containsKey(url))
	                {
	                    edges.add(file.getName() + " " + urlFileMap.get(url));
	                }
	            }	
			}
		}
		
		
		// Write to edgeList.txt
		FileWriter edge_list = new FileWriter("/Users/nehapathapati/Desktop/CSCI 572 - IR/Homework 4/edgeList.txt");
		int count = 0;
        for(String edge : edges)
        {
            System.out.println("Edge " + count + " " + edge);
            edge_list.append(edge + "\n");
            count++;
        }
        edge_list.close();
        System.out.println(count);
        System.out.println("Done");
  
	} // Main
} // Class
